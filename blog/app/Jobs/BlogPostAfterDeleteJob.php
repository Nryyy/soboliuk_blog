<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BlogPostAfterDeleteJob implements ShouldQueue
{
    use Queueable;

    /**
     * @var int
     */
    private $blogPostId;

    public function __construct($blogPostId)
    {
        $this->blogPostId = $blogPostId;
    }

    public function handle()
    {
        logs()->warning("Видалено запис в блозі [{$this->blogPostId}]");
    }
}
