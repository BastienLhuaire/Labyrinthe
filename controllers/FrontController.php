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
		$hauteur=(int)$data->hauteur;
		$largeur=(int)$data->largeur;
		$chemin=[];
		//genere la grille avec tout les murs
		for ($y=0; $y < $hauteur; $y++) { 
			$row=[];
			array_push($grille,$row);
			for ($x=0; $x < $largeur; $x++) { 
				$case=["visited"=>false,"mur"=>["bas"=>1,"droite"=>1,"gauche"=>1,"haut"=>1],"x"=>$x,"y"=>$y];
				array_push($grille[$y],$case);
			}
		};
		$CaseVisited=0;
		//valide la premiére case choisit aléatoirement
		$position=[
			"x"=>mt_rand(0,$hauteur-1),
			"y"=>mt_rand(0,$largeur-1)
		];
		$grille[$position["y"]][$position["x"]]["visited"]=true;
		$CaseVisited++;

		//tant que le nb de case visiter est inferieur a la taille de la grille
		while($CaseVisited<$hauteur*$largeur){
			$PosibleDir=[];
			//Droite
			if($position["x"]+1<$largeur){
				if ($grille[$position["y"]][$position["x"]+1]["visited"]==false) {
					array_push($PosibleDir, "Droite");
				}
			}
			//Gauche
			if($position["x"]-1>=0){
				if ($grille[$position["y"]][$position["x"]-1]["visited"]==false) {
					array_push($PosibleDir, "Gauche");
				}
			}
			//Haut
			if($position["y"]-1>=0){
				if ($grille[$position["y"]-1][$position["x"]]["visited"]==false) {
					array_push($PosibleDir, "Haut");
				}
			}
			//Bas
			if($position["y"]+1<$hauteur){
				if ($grille[$position["y"]+1][$position["x"]]["visited"]==false) {
					array_push($PosibleDir, "Bas");
				}
			}

			if(count($PosibleDir)>0){
				$CaseVisited++;
				array_push($chemin, $grille[$position["y"]][$position["x"]]);
				$direction = $PosibleDir[mt_rand(0,count($PosibleDir)-1)];
				switch($direction){
					case "Haut":
					//enleve le mur du haut et le mur du bas de la ou on vas
						$grille[$position["y"]][$position["x"]]["mur"]["haut"] = 0;
						$grille[$position["y"]-1][$position["x"]]["mur"]["bas"] = 0;
						$position["y"] --;
						break;
					case "Bas":
						$grille[$position["y"]][$position["x"]]["mur"]["bas"] = 0;
						$grille[$position["y"]+1][$position["x"]]["mur"]["haut"] = 0;
						$position["y"] ++;
						break;
					case "Droite":
						$grille[$position["y"]][$position["x"]]["mur"]["droite"] = 0;
						$grille[$position["y"]][$position["x"]+1]["mur"]["gauche"] = 0;
						$position["x"] ++;
						break;
					case "Gauche":
						$grille[$position["y"]][$position["x"]]["mur"]["gauche"] = 0;
						$grille[$position["y"]][$position["x"]-1]["mur"]["droite"] = 0;
						$position["x"] --;
						break;
				}
				//passe en visitez la nouvelle position
				$grille[$position["y"]][$position["x"]]["visited"]=true;
			}else{
				//recule d'une case
				$position = array_pop($chemin);
			}
		}
		//entrée
		$grille[0][0]["mur"]["gauche"]=0;
		$grille[$hauteur-1][$largeur-1]["mur"]["droite"]=0;
		//sortie
		return $this->app['twig']->render('Front/labyrinthe.twig', ['labyrinthe' => $grille, 'data' => $data]);
	}
}