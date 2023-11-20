<?php

namespace App\Helpers;

use Symfony\Component\Process\Process;

class Multi
{
    private  array $pullProcess;
    public function __construct(array $scripts, ?callable $callbackFunction = null)
    {

        foreach ($scripts as $script) {
            var_dump($script);
            $this->initProcess($script, $callbackFunction);
        }

        while (true) {
            sleep(1);
            
            $this->checkFinish();
        }
    }

    private function initProcess(mixed $script, ?callable $callbackFunction): Process
    {
        $process = new Process([ 'php', $script]);
        $process->start($callbackFunction);

        $this->pullProcess[$process->getPid() . ':' . $script] = $process;

        return $process;

    }

    private function checkFinish(): void
    {
        if (empty($this->pullProcess)) {
            return;
        }

        foreach ($this->pullProcess as $nameProcess => $process) {
            if ($process instanceof Process && $process->isTerminated()) {
                echo 'FINISH: ' . $nameProcess .'  ' . $process->getOutput();

                unset($this->pullProcess[$nameProcess]);
            }
        }
    }
}