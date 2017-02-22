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
	public function create(Request $request){
		$data=[
			"hauteur"=>$request->request->get('hauteur'),
			"largeur"=>$request->request->get('largeur'),
			"couleur"=>$request->request->get('couleur')
		];
		
		$prepare = $this->app['pdo']->prepare('
			DELETE FROM parameters;
			INSERT INTO parameters (hauteur,largeur,couleur) VALUES (?,?,?); ');
		$prepare->bindvalue(1,$data['hauteur'],\PDO::PARAM_INT);
		$prepare->bindvalue(2,$data['largeur'],\PDO::PARAM_INT);
		$prepare->bindvalue(3,$data['couleur'],\PDO::PARAM_STR);
		
		$prepare->execute();
		//$this->app['twig']->render('Front/home.twig', ['data' => $data])

		return $this->app->redirect('/');
		
	}
}

