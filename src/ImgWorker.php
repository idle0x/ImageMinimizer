<?php 

namespace Idleo\ImageOptimizer;


use Exception;

class ImgWorker 
{
    protected bool $rewriteExistFile;
    protected string $projectPath; // Root project folder containing $cliScript
    protected string $cliScript = '';

    public ImgParams $params;

    public function __construct(ImgParams $imageParams, string $projectPath = '', bool $rewriteExistFile = false)
    {
        $this->params = $imageParams;

        if (!empty($projectPath) && is_dir($projectPath)) {
            $this->projectPath = realpath($projectPath);
        } else {
            $this->projectPath = realpath($_SERVER['DOCUMENT_ROOT']);
        }

        $this->rewriteExistFile = $rewriteExistFile;
    }

    /**
     * Executed cli_script in console with parameters as cli arguments
     *
     * @throws Exception
     */
    public function execute(): int
    {
        if (!$this->params->isRequiredParamsFilled()) {
            throw new Exception('ERROR: Not filled required options');
        }
        $params = $this->params->getParams();
        $outputFile = "{$params['output']}{$params['filename']}.{$params['extension']}";
        if (file_exists($outputFile) && !$this->rewriteExistFile) {
            return 0;
        }

        $command = $this->prepareCommand($params);
        $out = [];
        $result = '';
        exec($command, $out, $result);
        if ($result != 0) {
            throw new Exception("ERROR: On command work (Result: $result; Command: $command)");
        }
        return $result;
    }

    protected function prepareCommand(array $imageParams): string
    {
        if (!file_exists($this->projectPath . DIRECTORY_SEPARATOR . $this->cliScript)) {
            throw new Exception('ERROR: Script for worker not exist');
        }
        $command = "cd {$this->projectPath} && \"{$this->nodeExecutePath()}\" $this->cliScript";
        foreach ($this->params->getParams() as $name=>$value) {
            if (!empty($value)) {
                $command .= " --$name $value";
            }
        }
        return $command;
    }

    /**
     * Check OS and return node execute path
     *
     * @return string
     */
    protected function nodeExecutePath(): string
    {
        if (substr(php_uname(), 0, 7) == "Windows") {
            return "C:\\Program Files\\nodejs\\node.exe";
        } else {
            return "/usr/bin/node";
        }
    }
}