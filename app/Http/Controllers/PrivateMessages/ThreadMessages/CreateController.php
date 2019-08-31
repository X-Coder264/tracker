<?php

declare(strict_types=1);

namespace App\Http\Controllers\PrivateMessages\ThreadMessages;

use Illuminate\Http\Response;
use App\Models\PrivateMessages\Thread;
use App\Models\PrivateMessages\ThreadMessage;
use Illuminate\Contracts\Routing\ResponseFactory;

final class CreateController
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(Thread $thread): Response
    {
        $threadMessage = new ThreadMessage();

        return $this->responseFactory->view(
            'private-messages.message-create',
            compact('thread', 'threadMessage')
        );
    }
}
