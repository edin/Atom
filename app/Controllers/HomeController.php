<?php

namespace App\Controllers;

use App\Models\UserRepository;
use Atom\View\ViewInfo;

final class HomeController
{
    public $UserRepository;
    public $View;
    public $Response;
    public $Request;
    public $Container;

    final public function index($id = 0, UserRepository $repository, \App\Application $app)
    {
        return new ViewInfo('home/index', ['items' => $repository->findAll()]);
    }

    final public function json(UserRepository $repository)
    {
        return [
            "items" => $repository->findAll(),
            "UserRepository" => $this->UserRepository,
            "View" => $this->View,
            "Request" => $this->Request,
            "Container" => $this->Container,
        ];
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