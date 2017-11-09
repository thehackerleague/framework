<?php

namespace Mods\View\Blocks;

use Mods\View\Block as BaseBlock;

class Form extends BaseBlock
{

	/**
     * The given form to render.
     *
     * @var string
     */
    protected $form;

    /**
     * Holds the setting for the form.
     *
     * @var array
     */
    protected $formSettings;


    /**
     * From instance.
     *
     * @var Mods\Form\Form
     */
    protected $formInstance;


	/**
	* {@inheritdoc}
	*/
	protected function boot()
    {
       $this->setTemplate('form.wrapper');
    }


	/**
	* {@inheritdoc}
	*/
    protected function _beforeToHtml()
    {   
        $this->formInstance = app()->make($this->form);

        $this->assign('form', 
            $this->getLayout()
                ->createBlock(EmptyBlock::class, 'form.elements')
                ->setTemplate('form.elements')
                ->assign($this->formInstance->build())
                ->toHtml()
        );

        return $this;
    }

    /**
    * Get the Title
    *
    * @return string
    */
    public function getTitle()
    {
        return '';
    }

    /**
    * Get the Icon
    *
    * @return string
    */
    public function getHeaderIcon()
    {
        return '';
    }


    /**
    * Get the Form Settings
    *
    * @return array
    */
    public function getSettings()
    {
        return $this->formInstance->settings();
    }

    /**
    * Check if the field has error.
    *  
    * @param string $field
    * @return bool
    */
    public function hasError($field)
    {
        return $this->formInstance->hasError($field);
    }

    /**
    * Get the field error.
    *  
    * @param string $field
    * @return string
    */
    public function getError($field)
    {
        return $this->formInstance->getError($field);
    }
	
}
