#!/bin/bash

split()
{
    SUBDIR=$1
    SPLIT=$2
    HEADS=$3

    mkdir -p $SUBDIR;

    pushd $SUBDIR;

    for HEAD in $HEADS
    do

        mkdir -p $HEAD

        pushd $HEAD

        git subsplit init git@github.com:thehackerleague/framework.git
        git subsplit update

        time git subsplit publish --heads="$HEAD" --no-tags "$SPLIT"

        popd

    done

    popd
    rm -rf $SUBDIR;
}

split theme     src/Thl/Theme:git@github.com:thl-framework/theme.git        "master"
split view      src/Thl/View:git@github.com:thl-framework/view.git          "master"
split backend   src/Thl/Backend:git@github.com:thl-framework/backend.git    "master"