<?php

namespace App\Rpc;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fibonacci-client'
)]
class FibonnaciClientCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fibonacci_rpc = new FibonacciRpcClient();
        $promises = [];
        for ($i = 1; $i <= 40; $i++) {
            $promises[$i] = $fibonacci_rpc->call($i);
        }

        /** @var RpcPromise[] $promises */
        $promises = \array_reverse($promises, true);

        foreach ($promises as $i => $promise) {
            echo \sprintf('Requested %d, Got %s', $i, $promise->await()->getBody()) . PHP_EOL;
        }

        return Command::SUCCESS;
    }
}