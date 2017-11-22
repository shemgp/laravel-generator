<?php

namespace InfyOm\Generator\Commands\Publish;

use File;
use InfyOm\Generator\Commands\BaseCommand;

class PublishBaseCommand extends BaseCommand
{
    public function handle()
    {
    }

    public function publishFile($sourceFile, $destinationFile, $fileName)
    {
        if (file_exists($destinationFile) && !$this->confirmOverwrite($destinationFile)) {
            return;
        }

        if (config('infyom.laravel_generator.default_layout'))
        {
            $tmp_contents = file_get_contents($sourceFile);
            $tmp_contents = str_replace("@extends('layouts.app')", "@extends('".config('infyom.laravel_generator.default_layout')."')", $tmp_contents);
            file_put_contents($destinationFile, $tmp_contents);
        }
        else
            copy($sourceFile, $destinationFile);

        $this->comment($fileName.' published');
        $this->info($destinationFile);
    }

    /**
     * @param $sourceDir
     * @param $destinationDir
     * @param $dirName
     * @param bool $force
     *
     * @return bool|void
     */
    public function publishDirectory($sourceDir, $destinationDir, $dirName, $force = false)
    {
        if (file_exists($destinationDir) && !$force && !$this->confirmOverwrite($destinationDir)) {
            return;
        }

        File::makeDirectory($destinationDir, 493, true, true);
        File::copyDirectory($sourceDir, $destinationDir);

        $this->comment($dirName.' published');
        $this->info($destinationDir);

        return true;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
}
