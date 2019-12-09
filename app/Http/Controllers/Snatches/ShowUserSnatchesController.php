<?php

declare(strict_types=1);

namespace App\Http\Controllers\Snatches;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

final class ShowUserSnatchesController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(UserRepository $userRepository, ResponseFactory $responseFactory)
    {
        $this->userRepository = $userRepository;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(User $user): Response
    {
        $snatches = $this->userRepository->getUserSnatches($user->id, $user->torrents_per_page);

        return $this->responseFactory->view('user-snatches.show', compact('snatches', 'user'));
    }
}
