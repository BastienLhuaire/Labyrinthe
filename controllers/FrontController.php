<?php namespace Controllers;

use Silex\Application as App;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FrontController {
	private $app;

	public function __construct(App $app) {
	$this->app = $app;
	}

	public function index(){
		return $this->app['twig']->render('Front/home.twig', ['data' => 'test']);
	}
}

