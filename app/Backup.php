<?php

namespace App;

use App\Backup\Operation;
use Illuminate\Support\Collection;
use Osmianski\Exceptions\NotImplemented;
use Symfony\Component\Console\Output\OutputInterface;

class Backup
{
    public function run(OutputInterface $output = null): void
    {
        foreach ($this->getSteps($output) as $step) {
            $step->run();
        }
    }

    /**
     * @return Collection<int, Operation>
     */
    protected function getSteps(OutputInterface $output = null): Collection
    {
        $parameters = [];

        if ($output) {
            $parameters['output'] = $output;
        }

        return collect(config('backup'))
            ->map(fn(array $data) => code()->instanceOf(
                Operation::class,
                $data['key'],
                array_merge($parameters, $data)
            ));
    }
}
