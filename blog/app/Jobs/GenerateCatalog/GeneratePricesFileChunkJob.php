<?php

namespace App\Jobs\GenerateCatalog;

class GeneratePricesFileChunkJob extends AbstractJob
{
    protected $chunk;
    protected $fileNum;

    public function __construct($chunk, $fileNum)
    {
        parent::__construct();
        $this->chunk = $chunk;
        $this->fileNum = $fileNum;
    }

    public function handle()
    {
        $this->debug('processing chunk ' . $this->fileNum . ' with products: ' . implode(',', $this->chunk->toArray()));
        parent::handle();
    }
}