<?php

class druid_arbitrage
{	
	private $errors = array();
	private $nivcarte = '';
	private $userlang = '';
	private $user= '';
	private $druid= '';
	
	private $raisin ='';
	private $res2 = '';
	private $res3 = '';
	private $result= '';
	private $mode = '';
	private $adresse = '';
	
	private $previousSGO = 0;
	private $previousSO = 0;
	private $pointsO = 10;
	
	private $previousSGDr = 0;
	private $previousSDr = 0;
	private $pointsDr = 10;
	
	private $et_c_est_le_temps_qui_court ='d/m/Y H:i';
	
	private $valid = 'valid';
	private $invalid = 'invalid';

	public function set_mode($mode)
	{
		$this->mode = $mode;
	}

	public function process()
	{
		if ( $this->init() )
        {
            $this->selectpartie();
            return $this->display_et_scores();
        }
        return false;
	}

	private function init()
	{
		//récupération des informations de bases : userid, langue et la date
		$this->user = user::getInstance();
		$this->druid = $this->user->id;
		$this->userlang = $this->user->userlang;
		
		$this->et_c_est_le_temps_qui_court = date("d/m/Y H:i");
		
		return true;
	}

	private function selectpartie()
	{
		//connexion à la BD
		$db = db::getInstance();
		
		// récupération d'un enregistrement au hasard
		$sql = 'SELECT 
                    enregistrementID,cheminEnregistrement,idOracle,carteID,nivcarte 
                    FROM enregistrement WHERE idOracle!='.$this->druid.' AND OracleLang="'.$this->userlang.'" ORDER BY RAND() LIMIT 1';	    
        $this->result=$db->query($sql);
        $this->raisin= mysqli_fetch_assoc($this->result);
		
		// récupération du pseudo du joueur arbitré
		 $sql = 'SELECT 
                    username
                    FROM user WHERE userid ="'.$this->raisin['idOracle'].'"';	    
        $this->result=$db->query($sql);
         $this->res2= mysqli_fetch_assoc($this->result);
        
        //récupération de la carte jouée
		 $sql = 'SELECT 
                    niveau,mot,tabou1,tabou2,tabou3,tabou4,tabou5 
                    FROM carte WHERE carteID='.$this->raisin['carteID'].'';	    
        $this->result=$db->query($sql);
        $this->res3= mysqli_fetch_assoc($this->result);
        
		//construction de l'adresse de l'enregistrement à partir du nom du fichier
        $this->adresse = "enregistrements/".$this->raisin['cheminEnregistrement'];
        
		return true;
	}

	private function display_et_scores()
	{
		// après avoir cliqué sur "au bûcher" = description vide ou fautive
		if(isset($_POST['invalidate']))
		{
			//connexion à la BD
			$db = db::getInstance();
			
			// Requête d'insertion des info dans la table 'arbitrage'
			$sql = 'INSERT INTO arbitrage
			(enregistrementID,idDruide,tpsArbitrage,validation)
				VALUES(' .
					$db->escape((string) $this->raisin['enregistrementID']) . ', ' .
					$db->escape((string) $this->druid) . ', ' .
					$db->escape((string) $this->et_c_est_le_temps_qui_court) . ', ' .
					$db->escape((string) $this->invalid ) . ')' ;
					
				$db->query($sql);
					
			// Mise en commentaire provisoirement. Servirait à supprimer la description fautive pour les arbitres ne tombent plus dessus
				//$sql = 'DELETE 
				//FROM enregistrement WHERE carteID='.$this->raisin['carteID'].'';
				//$db->query($sql);
			
		// Requête de modification du score de l'Oracle dont la description est jetée en pâture aux flammes du bûcher purificateur
			
			//récupération du score précédent;
			$sql = 'SELECT `scoreGlobal`,`scoreOracle` FROM `score` WHERE `userid`="'.$this->raisin['idOracle'].'"';
			$result=$db->query($sql);
			$res= mysqli_fetch_assoc($result);

			$this->previousSGO= $res['scoreGlobal'];
			$this->previousSO= $res['scoreOracle'];

			// ici peut-être prévoir une requête qui vérifie si cette partie a déjà été arbitrée pour éviter d'enlever trop de points sur une même description
			
			//maj des variables de scores: le score ne doit jamais être négatif mais il peut être nul.
			if($this->previousSO>=$this->pointsO)
			{
				$this->previousSGO= $this->previousSGO - $this->pointsO;
				$this->previousSO= $this->previousSO - $this->pointsO;
			}
			
			//maj du score dans la BD
			$sql = 'UPDATE score 
					SET  scoreGlobal='.$db->escape((string) $this->previousSGO) . ', ' .
					'scoreOracle='.$db->escape((string) $this->previousSO) . '
					WHERE userid='.$this->raisin['idOracle'].'';
					
			$db->query($sql);
			
		//Requête de modification du score du Druide après l'accomplissement de son fastidieux travail d'inquisition
			//récupération du score précédent;

			$sql = 'SELECT `scoreGlobal`,`scoreDruide` FROM `score` WHERE `userid`="'.$this->druid.'"';
			$result=$db->query($sql);
			$res= mysqli_fetch_assoc($result);

			$this->previousSGDr= $res['scoreGlobal'];
			$this->previousSDr= $res['scoreDruide'];
			
			//maj des variables de scores

			$this->previousSGDr= $this->previousSGDr+$this->pointsDr;
			$this->previousSDr= $this->previousSDr+$this->pointsDr;
			
			//maj du score dans la BD
			$sql = 'UPDATE score 
					SET  scoreGlobal='.$db->escape((string) $this->previousSGDr) . ', ' .
					'scoreDruide='.$db->escape((string) $this->previousSDr) . '
					WHERE userid='.$this->druid.'';	
			$db->query($sql);
			
			// affichage de la page de résultat
			include('./views/druid.result.html');
			
			// après avoir cliqué sur "valider" = description correcte et jouable
		}elseif (isset($_POST['validate'])){

				//connexion à la BD
				$db = db::getInstance();
				// insertion des informations dans la table arbitrage
				$sql = 'INSERT INTO arbitrage
				(enregistrementID,idDruide,tpsArbitrage,validation)
					VALUES(' .
						$db->escape((string) $this->raisin['enregistrementID']) . ', ' .
						$db->escape((string) $this->druid) . ', ' .
						$db->escape((string) $this->et_c_est_le_temps_qui_court) . ', ' .
						$db->escape((string) $this->valid ) . ') ' ;	
					$db->query($sql);
				
				//	mettre à jour le champs "validation" de la table enregistrement pour que cet enregistrement devienne jouable
				$sql = 'UPDATE enregistrement 
				SET validation =  ' .$db->escape((string) $this->valid ) . ' 
				WHERE enregistrementID="'.$this->raisin['enregistrementID'].'" ' ;
					
				$db->query($sql);	
				
			// Requête de modification du score de l'Oracle dont la description est élevée au rang de prediction divine
			
				//récupération du score précédent;
				$sql = 'SELECT `scoreGlobal`,`scoreOracle` FROM `score` WHERE `userid`="'.$this->raisin['idOracle'].'"';
				$result=$db->query($sql);
				$res= mysqli_fetch_assoc($result);

				$this->previousSGO= $res['scoreGlobal'];
				$this->previousSO= $res['scoreOracle'];
				
				// ici peut-être prévoir une requête qui vérifie si cette partie a déjà été arbitrée 
			
				//maj des variables de scores: le score ne doit jamais être négatif.
	
				$this->previousSGO= $this->previousSGO+$this->pointsO;				
				$this->previousSO= $this->previousSO+$this->pointsO;				
			
				//maj du score dans la BD
				$sql = 'UPDATE score 
					SET  scoreGlobal='.$db->escape((string) $this->previousSGO) . ', ' .
					'scoreOracle='.$db->escape((string) $this->previousSO) . '
					WHERE userid='.$this->raisin['idOracle'].'';
					
				$db->query($sql);
				
		//Requête de modification du score du Druide l'accomplissement de son fastidieux travail d'inquisition
			//récupération du score précédent;

			$sql = 'SELECT `scoreGlobal`,`scoreDruide` FROM `score` WHERE `userid`="'.$this->druid.'"';
			$result=$db->query($sql);
			$res= mysqli_fetch_assoc($result);

			$this->previousSGDr= $res['scoreGlobal'];
			$this->previousSDr= $res['scoreDruide'];
			
			//maj des variables de scores: le score ne doit jamais être négatif.

			$this->previousSGDr= $this->previousSGDr+$this->pointsDr;
			$this->previousSDr= $this->previousSDr+$this->pointsDr;
			
			//maj du score dans la BD
			$sql = 'UPDATE score 
					SET  scoreGlobal='.$db->escape((string) $this->previousSGDr) . ', ' .
					'scoreDruide='.$db->escape((string) $this->previousSDr) . '
					WHERE userid='.$this->druid.'';	
			$db->query($sql);
				
				//affichage de la page de résultat
				include('./views/druid.result.html');	
			// sinon, c'est le premier passage dans la page, il n'y a pas encore eu d'arbitrage donc on affiche la page d'arbitrage
		}else{
					include('./views/druid.arbitrage.html');
		}
        return true;
	}
}

?>
