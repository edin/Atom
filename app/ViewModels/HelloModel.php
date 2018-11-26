<?php

namespace App\ViewModels;

class HelloModel implements IViewFile {
    public function getViewFile() {
        return "@views/hello-model.latte";
    }
}

interface IResponseHandler {
    public function isMatch($response): boolean;
    public function process($response): IResponse;
}

interface IRequest {}
interface IResponse {}
interface IResponseHandler {}
interface IParameterResolver {}
interface IRouter {}
interface IConfiguration {}
interface IContainer {}
interface IControllerActionProvider {}
interface IUser {}
interface IViewLocator {}
interface IParameterResolver {}

interface IServiceProvider {
    function getService($name);
}

interface IConfigurationProvider {
    function getConfiguration($name);
}