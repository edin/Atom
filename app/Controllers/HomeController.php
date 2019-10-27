<?php

namespace App\Controllers;

use App\Messages\FormPostMessage;
use App\Models\UserRepository;
use Atom\View\ViewInfo;
use Atom\View\View;
use Atom\Container\Container;
use Atom\Database\Connection;
use Psr\Http\Message\ServerRequestInterface;

final class HomeController
{
    private $UserRepository;
    private $View;
    private $Request;
    private $Container;

    public function __construct(
        UserRepository $userRepository,
        View $view,
        ServerRequestInterface $request,
        Container $container
    ) {
        $this->UserRepository = $userRepository;
        $this->View = $view;
        $this->Request = $request;
        $this->Container = $container;
    }

    final public function index($id = 0, FormPostMessage $post)
    {
        return new ViewInfo(
            'home/index',
            [
                'items' => $this->UserRepository->findAll(),
                'post' => $post
            ]
        );
    }

    final public function json(UserRepository $repository)
    {
        $db = new Connection(Connection::MySQL, "localhost", "root", "root", "developers");
        $rows = $db->queryAll("SELECT * FROM skills");

        // Query::select()
        //     ->from("users u")
        //     ->columns([
        //         "u.a", "b", "c", "d",
        //     ])
        //     ->show();
        return $rows;
    }

    final public function item()
    {
        $item = new \stdClass;
        $item->title = "Item";
        return new ViewInfo('home/item', ['item' => $item]);
    }

    final public function onGet(UserRepository $repository)
    {
        return $repository->findAll();
    }

    final public function onPost()
    {
        return ["result" => "Executed onPost method."];
    }

    final public function onPut($id = 0)
    {
        return ["result" => "Executed onPut method.", "id" => $id];
    }

    final public function onPatch()
    {
        return ["result" => "Executed onPatch method."];
    }

    final public function onDelete()
    {
        return ["result" => "Executed onDelete method."];
    }

    final public function onOptions()
    {
        return ["result" => "Executed onOptions method."];
    }
}
