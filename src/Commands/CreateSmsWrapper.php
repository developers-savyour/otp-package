<?php

namespace Savyour\SmsAndEmailPackage\Commands;

use Illuminate\Console\Command;
use Savyour\SmsAndEmailPackage\FilesBlueprints\SmsWraperBlueprint;

class CreateSmsWrapper extends Command
{
    protected $fileName = '';
    protected $signature = 'SmsWrapper:Create {--name=""}';
    protected $description = 'Create Sms Service Wrapper';
    protected $fileRelativePath = '';
    protected $interFaceFileName = 'SmsWrapperInterface.php';

    public function handle()
    {

        $this->fileName = ucfirst($this->option('name'));
        // check user did send file name or else show error
        if(empty($this->fileName))
        {
            $this->error('Please send a file name');
            return false;
        }

        // upddate file relative path
        $this->fileRelativePath = config('config-sms-and-email-package-service.wrapper_creation_path').'/Sms';

        // create file
        $this->resloveFileCreation();
        $this->error('The wrapper is created successfully to '. $this->fileRelativePath.'/'.$this->fileName.'.php');

    }


    protected function resloveFileCreation()
    {

        $fileCreationPath = app_path().'/'.$this->fileRelativePath;

        // checking the directory exists or create
        if(!is_dir($fileCreationPath))
        {
            mkdir($fileCreationPath,0777,true);
        }

        // checking the file exist or not
        if(!file_exists($fileCreationPath.'/'.$this->interFaceFileName))
        {
            // get file content
            $fileBluePrint = trim(SmsWraperBlueprint::getInterFaceContent());
            $fileBluePrint = str_replace('{FilePath}',str_replace('/','\\',$this->fileRelativePath),$fileBluePrint);
            // open or create file
            $file = fopen($fileCreationPath.'/'.$this->interFaceFileName, 'w+');
            fwrite($file, $fileBluePrint);
            fclose($file);
        }


        // get file content
        $fileBluePrint = trim(SmsWraperBlueprint::getFileContent());
        $fileBluePrint = str_replace('{FilePath}',str_replace('/','\\',$this->fileRelativePath),$fileBluePrint);
        $fileBluePrint = str_replace('{ClassName}',$this->fileName,$fileBluePrint);

        // open or create file
        $file = fopen($fileCreationPath.'/'.$this->fileName.'.php', 'w+');
        fwrite($file, $fileBluePrint);
        fclose($file);




    }


}