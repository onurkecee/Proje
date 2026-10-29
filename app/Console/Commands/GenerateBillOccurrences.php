<?php

namespace App\Console\Commands;

use App\Services\BillOccurrenceGenerator;
use Illuminate\Console\Command;

class GenerateBillOccurrences extends Command
{
    protected $signature = 'bills:generate-occurrences';

    protected $description = 'Generate upcoming monthly bill due dates';

    public function handle(BillOccurrenceGenerator $generator): int
    {
        $created = $generator->generateForActiveBills();

        $this->info("{$created} fatura dönemi oluşturuldu.");

        return self::SUCCESS;
    }
}
