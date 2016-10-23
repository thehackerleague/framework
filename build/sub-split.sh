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

split foundation    src/Mods/Theme:git@github.com:mods-framework/foundation.git     "master"
split http          src/Mods/Theme:git@github.com:mods-framework/http.git           "master"
split support       src/Mods/Theme:git@github.com:mods-framework/support.git        "master"
split theme         src/Mods/Theme:git@github.com:mods-framework/theme.git          "master"
split view          src/Mods/View:git@github.com:mods-framework/view.git            "master"