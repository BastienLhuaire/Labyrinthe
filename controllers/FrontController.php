<?php namespace Controllers;

use Silex\Application as App;
use Symfony\Component\Validator\Constraints as Assert;
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
		return $this->app['twig']->render('Front/home.twig', ['data'=>"none"]);
	}
	public function create(Request $request){
		$data=[
			"hauteur"=>$request->request->get('hauteur'),
			"largeur"=>$request->request->get('largeur'),
			"couleur"=>$request->request->get('couleur')
		];
		$constraint = new Assert\Collection([
			'hauteur'    => [
				new Assert\NotBlank([
					'message' => 'ce champ ne doit pas être vide']), 
				new Assert\Type([
					'value'   => 'numeric',
					'message' => 'nombre demander']),	
				new Assert\Regex([
					'message' => 'la hauteur doit être positif',
					'pattern' => '/^[1-9][0-9]*$/'
			])],
			'largeur'    => [
				new Assert\NotBlank([
					'message' => 'ce champ ne doit pas être vide']), 
				new Assert\Type([
					'value'   => 'numeric',
					'message' => 'nombre demander']), 
				new Assert\Regex([
					'message' => 'La largeur doit être positif',
					'pattern' => '/^[1-9][0-9]*$/'
			])],
			'couleur'	=> [
				new Assert\NotBlank([
					'message' => 'ce champ ne doit pas être vide']), 
				new Assert\Type([
					'value'   => 'string',
					'message' => 'il faut une couleur'])
				]
		]);
		$errors = $this->app['validator']->validate($data, $constraint);

		echo (string) $errors;

		if (count($errors) > 0) {
			$this->app['session']->getFlashBag()->add('errors', (string) $errors);
			return $this->app->redirect('/');
		}

		$prepare = $this->app['pdo']->prepare('
			DELETE FROM parameters;
			INSERT INTO parameters (hauteur,largeur,couleur) VALUES (?,?,?); ');
		$prepare->bindvalue(1,$data['hauteur'],\PDO::PARAM_INT);
		$prepare->bindvalue(2,$data['largeur'],\PDO::PARAM_INT);
		$prepare->bindvalue(3,$data['couleur'],\PDO::PARAM_STR);
		
		$prepare->execute();

	    //return $this->app['twig']->render('Front/labyrinthe.twig', ['data' => $data]);
		return $this->app->redirect('/labyrinthe');
	}
	public function generate(){
		$prepare = $this->app['pdo']->prepare('SELECT * FROM parameters');
	  	$prepare->execute();
	  
	    $data = $prepare->fetch();
		$grille=[];
		//genere la grille avec tout les murs
		for ($y=0; $y < (int)$data->hauteur; $y++) { 
			$row=[];
			array_push($grille,$row);
			for ($x=0; $x < (int)$data->largeur; $x++) { 
				$case=["visited"=>false,"mur"=>["bas"=>1,"droite"=>1],"x"=>$x,"y"=>$y];
				array_push($grille[$y],$case);
			}
		};

		$caseCoo=[
			"x"=>mt_rand(0,$data->hauteur-1),
			"y"=>mt_rand(0,$data->largeur-1)
		];
		function deleteWalls($grille,$caseEnCour,$chemin){
			$directionPossible=[];
			$tabDirection=["bas","droite","haut","gauche"];
			$caseEnCour["visited"]=true;
			// Verifier les direction possible du chemin
			for ($i=0; $i < 4; $i++) {
				$direction=$tabDirection[$i]; 
				switch ($direction) {
					case 'bas':
						if (!is_null($grille[$caseEnCour["y"]+1][$caseEnCour["x"]])) {
							$cell = $grille[$caseEnCour["y"]+1][$caseEnCour["x"]] ;
							if (!$cell["visited"]) {
								$directionPossible["bas"]=$cell;
							}
						}
						break;
					case 'droite':
						if (!is_null($grille[$caseEnCour["y"]][$caseEnCour["x"]+1])) {
							$cell = $grille[$caseEnCour["y"]][$caseEnCour["x"]+1] ;
							if (!$cell["visited"]) {
								$directionPossible["droite"]=$cell;
							}
						}
						break;
					case 'haut':
						if (!is_null($grille[$caseEnCour["y"]-1][$caseEnCour["x"]])) {
							$cell = $grille[$caseEnCour["y"]-1][$caseEnCour["x"]] ;
							if (!$cell["visited"]) {
								$directionPossible["haut"]=$cell;
							}
						}
						break;
					case 'gauche':
						if (!is_null($grille[$caseEnCour["y"]][$caseEnCour["x"]-1])) {
							$cell = $grille[$caseEnCour["y"]][$caseEnCour["x"]-1] ;
							if (!$cell["visited"]) {
								$directionPossible["gauche"]=$cell;
							}
						}
						break;
				}
			}
			//on choisit parmis les possibilités aléatoirement
			if ($directionPossible>0) {
				$direction=array_rand($directionPossible);
				$newCase=$directionPossible[$direction];
				switch ($direction) {
					case 'bas':
					//supprime le mur dans la grille;
						$grille[$caseEnCour["y"]][$caseEnCour["x"]]["murs"]["bas"]=0;
						$caseEnCour["murs"]["bas"]=0;
						array_push($chemin, $caseEnCour);
						$caseEnCour=$newCase;
						//recursivité
						deleteWalls($grille,$caseEnCour,$chemin);
						break;
					
					case 'droite':
						//supprime le mur dans la grille;
						$grille[$caseEnCour["y"]][$caseEnCour["x"]]["murs"]["droite"]=0;
						$caseEnCour["murs"]["droite"]=0;
						array_push($chemin, $caseEnCour);
						$caseEnCour=$newCase;
						//recursivité
						deleteWalls($grille,$caseEnCour,$chemin);
						break;

					case 'haut':
					//supprime le mur du bas de la case du haut dans la grille;
						$grille[$caseEnCour["y"]-1][$caseEnCour["x"]]["murs"]["bas"]=0;
						$caseEnCour["murs"]["bas"]=0;
						array_push($chemin, $caseEnCour);
						$caseEnCour=$newCase;
						//recursivité
						deleteWalls($grille,$caseEnCour,$chemin);
						break;

					case 'gauche':
					//supprime le mur de droite de la case de gauche dans la grille;
						$grille[$caseEnCour["y"]][$caseEnCour["x"-1]]["murs"]["bas"]=0;
						$caseEnCour["murs"]["droite"]=0;
						array_push($chemin, $caseEnCour);
						$caseEnCour=$newCase;
						//recursivité
						deleteWalls($grille,$caseEnCour,$chemin);
						break;
				}
			}else{
				//on recule dans le chemin si pas de possibilités
				$previousCell = $chemin[count($chemin)-1];
				$caseEnCour = $previousCell;
				deleteWalls($grille,$caseEnCour,$chemin);
			}
		}
		// echo '<pre>';
		// echo print_r();
		// echo '<pre>';
		$chemin=[];
		deleteWalls($grille,$grille[$caseCoo["y"]][$caseCoo["x"]],$chemin);
		return $this->app['twig']->render('Front/home.twig', ['labyrinthe' => $grille, 'data' => $data]);
	}
}