<?php

namespace Mods\Foundation\Aspect\Command;

use Illuminate\Console\Command;
use Mods\Foundation\Aspect\Proxified;
use Psr\Container\ContainerInterface;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Mods\Foundation\Aspect\AdviceManager;
use Psr\Container\NotFoundExceptionInterface;

class Inspect extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'aspect:inspect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect the register aspect.';

   
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $advices = $this->laravel['aspect']->all();

        $this->line('================');

        $this->info('Cleaning old files !!!!');

        $this->cleanDirectory();        

        foreach ($advices as $targetClass => $advice) {
            $this->info("\tIntercepting :: {$targetClass}");

            try {
                $target = $this->laravel->get($targetClass);
            } catch (NotFoundExceptionInterface $e) {
                $this->warn("\tOops!!! `{$targetClass}` :: has no bindings or binding not defined");
                continue;
            }

            $namespace = get_class($target);
            $className = $namespace.'\\Proxy';

            $this->info("\tCreating Proxy Class for {$targetClass}");

            $code = $this->generateClass($className, $this->getClassMethods($target, array_flip(array_keys($advice))), $namespace);

            if ($this->saveGeneratedCode($namespace, 'Proxy', $code) === true) {
                $this->laravel['aspect']->processed($targetClass);
            }
            $this->info('');
        }
    }

    protected function generateClass($className, $methods, $extends)
    {
        $classCode  = new ClassGenerator();

        $classCode->setName($className)
            ->setExtendedClass($extends)
            ->addUse(AdviceManager::class)
            ->addTraits(['\\'.Proxified::class])
            ->addMethods($methods);

        $file = FileGenerator::fromArray([
            'classes'  => [$classCode],
        ]);

        return $file->generate();
    }

    /**
     * Returns list of methods for given class
     *
     * @return mixed
     */
    protected function getClassMethods($source, $onlyMethods)
    {
        $methods = [MethodGenerator::fromArray($this->getDefaultConstructorDefinition($source))];

        $reflectionClass = new \ReflectionClass($source);
        $publicMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($publicMethods as $method) {
            if ($this->isInterceptedMethod($method) && isset($onlyMethods[$method->getName()])) {
                $methods[] = MethodGenerator::fromArray($this->getMethodInfo($method));
            }
        }
        return $methods;
    }

    /**
     * Get default constructor definition for generated class
     *
     * @return array
     */
    protected function getDefaultConstructorDefinition($source)
    {
        $reflectionClass = new \ReflectionClass($source);
        $constructor = $reflectionClass->getConstructor();
        $parameters = [];
        $body = "\$this->initlize();\n";
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $parameters[] = $this->getMethodParameterInfo($parameter);
            }
            $body .= count($parameters)
                ? "parent::__construct({$this->getParameterList($parameters)});"
                : "parent::__construct();";
        }
        return [
            'name' => '__construct',
            'parameters' => $parameters,
            'body' => $body
        ];
    }

    /**
     * Whether method is intercepted
     *
     * @param \ReflectionMethod $method
     * @return bool
     */
    protected function isInterceptedMethod(\ReflectionMethod $method)
    {
        return !($method->isConstructor() || $method->isFinal() || $method->isStatic() || $method->isDestructor()) &&
            !in_array($method->getName(), ['__sleep', '__wakeup', '__clone']);
    }

    /**
     * Retrieve method info
     *
     * @param \ReflectionMethod $method
     * @return array
     */
    protected function getMethodInfo(\ReflectionMethod $method)
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = $this->getMethodParameterInfo($parameter);
        }

        $methodInfo = [
            'name' => ($method->returnsReference() ? '& ' : '') . $method->getName(),
            'parameters' => $parameters,
            'body' => "\$adviceList = \$this->container['aspect']->get(\$this->subject.'@{$method->getName()}');\n" .
                "if (empty(\$adviceList)) {\n" .
                "    return parent::{$method->getName()}({$this->getParameterList(
                $parameters
            )});\n" .
            "} else {\n" .
            "    return \$this->callAdvices('{$method->getName()}', func_get_args(), \$adviceList);\n" .
            "}",
            'returnType' => $method->getReturnType(),
            'docblock' => ['shortDescription' => '{@inheritdoc}'],
        ];

        return $methodInfo;
    }

    /**
     * @param array $parameters
     * @return string
     */
    protected function getParameterList(array $parameters)
    {
        return implode(
            ', ',
            array_map(
                function ($item) {
                    return "$" . $item['name'];
                },
                $parameters
            )
        );
    }

    /**
     * Retrieve method parameter info
     *
     * @param \ReflectionParameter $parameter
     * @return array
     */
    protected function getMethodParameterInfo(\ReflectionParameter $parameter)
    {
        $parameterInfo = [
            'name' => $parameter->getName(),
            'passedByReference' => $parameter->isPassedByReference(),
            'type' => $parameter->getType()
        ];

        if ($parameter->isArray()) {
            $parameterInfo['type'] = 'array';
        } elseif ($parameter->getClass()) {
            $parameterInfo['type'] = $this->getFullyQualifiedClassName($parameter->getClass()->getName());
        } elseif ($parameter->isCallable()) {
            $parameterInfo['type'] = 'callable';
        }

        if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
            $defaultValue = $parameter->getDefaultValue();
            if (is_string($defaultValue)) {
                $parameterInfo['defaultValue'] = $parameter->getDefaultValue();
            } elseif ($defaultValue === null) {
                $parameterInfo['defaultValue'] = $this->getNullDefaultValue();
            } else {
                $parameterInfo['defaultValue'] = $defaultValue;
            }
        }

        return $parameterInfo;
    }

    /**
     * Get fully qualified class name
     *
     * @param string $className
     * @return string
     */
    protected function getFullyQualifiedClassName($className)
    {
        $className = ltrim($className, '\\');
        return $className ? '\\' . $className : '';
    }

    /**
     * Get value generator for null default value
     *
     * @return \Zend\Code\Generator\ValueGenerator
     */
    protected function getNullDefaultValue()
    {
        $value = new \Zend\Code\Generator\ValueGenerator(null, \Zend\Code\Generator\ValueGenerator::TYPE_NULL);

        return $value;
    }

    /**
     * Save the Generated class code.
     *
     * @return bool
     */
    protected function saveGeneratedCode($namespace, $fileName, $code)
    {
        $directory = 'generated/classes/'.ltrim(str_replace(['\\', '_'], '/', $namespace), '/');
        if($this->laravel['files']->makeDirectory(resource_path($directory), 0755, true, true)) {
            if($this->laravel['files']->put(resource_path($directory.'/'.$fileName.'.php'), $code) !==  false) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Clean the directory.
     *
     * @return void
     */
    protected function cleanDirectory()
    {
        $this->laravel['files']->cleanDirectory(resource_path('generated/classes/'));
    }
}
