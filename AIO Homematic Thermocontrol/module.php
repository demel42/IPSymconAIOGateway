<?

require_once(__DIR__ . "/../AIOGatewayClass.php");  // diverse Klassen

class AIOHomematicThermocontrol extends IPSModule
{

   
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // 1. Verf�gbarer AIOSplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
        $this->ConnectParent("{7E03C651-E5BF-4EC6-B1E8-397234992DB4}");
		
		$this->RegisterPropertyString("HomematicAddress", "");
		$this->RegisterPropertyString("HomematicData", "");
		$this->RegisterPropertyString("HomematicType", "0095");
		$this->RegisterPropertyString("HomematicTypeName", "HM-CC-RT-DN");
		$this->RegisterPropertyString("HomematicSNR", "");
		$this->RegisterPropertyBoolean("LearnAddressHomematic", false);
		
    }


    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
		
		// HomematicAddress pr�fen
        $HomematicAddress = $this->ReadPropertyString('HomematicAddress');
        $LearnAddressHomematic = $this->ReadPropertyBoolean('LearnAddressHomematic');
				
		if ($LearnAddressHomematic)
		{
			$this->Learn();
		}
		elseif ( $HomematicAddress == '')
        {
            // Status inaktiv
            $this->SetStatus(104);
        }
		else 
		{
			//Eingabe �berpr�fen
			
			
			// Status aktiv
            $this->SetStatus(102);
			//Status-Variablen anlegen
			/*
			�	current temperature
			�	error
			�	humidity
			�	mode
			�	set temperature
			�	valve position

			*/
			$CurrentTemperatureId = $this->RegisterVariableFloat("CurrentTemperature", "Aktuelle Temperatur", "~Temperature", 1);
			//$this->EnableAction("CurrentTemperature");
			$ErrorId = $this->RegisterVariableBoolean("Error", "Error", "~Switch", 1);
			//$this->EnableAction("Error");
			$HumidityId = $this->RegisterVariableFloat("Humidity", "Luftfeuchtigkeit", "~Humidity.F", 1);
			//$this->EnableAction("Humidity");
			$associations =  Array(
									Array(0, "Manu",  "", -1),
									Array(1, "Auto",  "", -1)
									);
			$this->SetupProfile(IPSVarType::vtInteger, "AIOHM.TempMode", "Temperature", "", "", 0, 1, 1, 0, $associations);
			$ModeId = $this->RegisterVariableInteger("Mode", "Status", "AIOHM.TempMode", 1);
			$this->EnableAction("Mode");
			$SetTemperatureId = $this->RegisterVariableFloat("SetTemperature", "Status", "~Temperature", 1);
			$this->EnableAction("SetTemperature");
			$ValvePositionId = $this->RegisterVariableInteger("ValvePosition", "Ventilstellung", "~Intensity.100", 1);
			//$this->EnableAction("ValvePosition");
		}	
		
				
        // Profile anlegen

        
	}
	
	/**
    * Die folgenden Funktionen stehen automatisch zur Verf�gung, wenn das Modul �ber die "Module Control" eingef�gt wurden.
    * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verf�gung gestellt:
    *
    * ABC_MeineErsteEigeneFunktion($id);
    *
    */
		
	public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
				case "STATE":
					$this->HomematicPowerSetState($Value);
					break;
				default:
					throw new Exception("Invalid ident");
			}
		}	
	
	protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);//array
		return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;//ConnectionID
    }
	
	public function PowerOn() {
            $HomematicAddress = $this->ReadPropertyString("HomematicAddress");
			$action = "1";
			return $this->Send_Homematic($HomematicAddress, $action);	
        }
		
	public function PowerOff() {
			$HomematicAddress = $this->ReadPropertyString("HomematicAddress");
			$action = "4";
			return $this->Send_Homematic($HomematicAddress, $action);	
        }
	
		
	//IP Gateway 
	protected function GetIPGateway(){
		$ParentID = $this->GetParent();
		$IPGateway = IPS_GetProperty($ParentID, 'Host');
		return $IPGateway;
	}
	
	protected function GetPassword(){
		$ParentID = $this->GetParent();
		$GatewayPassword = IPS_GetProperty($ParentID, 'Passwort');
		return $GatewayPassword;
	}
	
	protected function HomematicPowerSetState ($state){
	SetValueBoolean($this->GetIDForIdent('STATE'), $state);
	return $this->SetPowerState($state);	
	}
	
	protected function SetPowerState($state) {
		$ELROAddress = $this->ReadPropertyString("HomematicAddress");
		if ($state === true)
		{
		$action = "1";
		return $this->Send_Homematic($HomematicAddress, $action);	
		}
		else
		{
		$action = "4";
		return $this->Send_Homematic($HomematicAddress, $action);	
		}
	}
	
		
	//Senden eines Befehls an Homematic
	protected function Send_Homematic($HomematicAddress, $action)
		{
		$GatewayPassword = $this->GetPassword();
		//ELRO Befehl, erste 5 Zeichen Adresse letzes Zeichen Command
		//$ELRO_send = substr($ELRO_send, 0, 5);
		$Homematic_send = $HomematicAddress;
		if ($action === "1")
			{
			// Sendestring Homematic ?????? /command?XC_FNC=SendSC&type=ELRO&data=
			if ($GatewayPassword !== "")
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."1"); //??
				$this->SendDebug("String to AIO Gateway","http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."1",0);
			}
			else
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."1");
				$this->SendDebug("String to AIO Gateway","http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."1",0);
			}
			
			$status = true;
			return $status;
			}
		else
			{
			if ($GatewayPassword != "")
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."4");
				$this->SendDebug("String to AIO Gateway","http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."4",0);
			}
			else
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."4");
				$this->SendDebug("String to AIO Gateway","http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=ELRO&data=".$Homematic_send."4",0);
			}
			
			$status = false;
			return $status;
			}

		}
	
	private $response = false;
	//Anmelden eines Homematic Ger�ts an das a.i.o. gateway:
	//http://{IP-Adresse-des-Gateways}/command?XC_FNC=LearnSC&type=ELRO
	public function Learn()
		{
		$HomematicSNR = $this->ReadPropertyString('HomematicSNR');	
		$GatewayPassword = $this->GetPassword();	
		if ($GatewayPassword !== "")
			{
				$aioresponse = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=learnSC&adr=".$HomematicSNR);
				$this->SendDebug("String to AIO Gateway","/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=learnSC&adr=".$HomematicSNR,0);
				$this->SendDebug("Homematic Adress",$address,0);
			}
			else
			{
				$aioresponse = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=learnSC&adr=".$HomematicSNR);
				$this->SendDebug("String to AIO Gateway","http://".$this->GetIPGateway()."/command?XC_FNC=learnSC&adr=".$HomematicSNR,0);
				$this->SendDebug("Homematic Adress",$address,0);
			}
		//kurze Pause w�hrend das Gateway im Lernmodus ist
		IPS_Sleep(1000); //1000 ms
		if ($aioresponse == "{XC_ERR}Failed to learn code")//Bei Fehler
			{
			$this->response = false;
			$instance = IPS_GetInstance($this->InstanceID)["InstanceID"];
			$address = "Das Gateway konnte keine Adresse empfangen.";
			$this->SendDebug("Homematic Adresse:",$address,0);
			IPS_LogMessage( "Homematic Adresse:" , $address );
			echo "Die Adresse vom Homematic Ger�t konnte nicht angelernt werden.";
			IPS_SetProperty($instance, "LearnAddressHomematic", false); //Haken entfernen.			
			}
		else
			{
				//Adresse auswerten {XC_SUC}
				//bei Erfolg {XC_SUC}{"adr":"130B99", "type":"0011"} 
				$length = strlen($aioresponse);
				(string)$jsonresponse = substr($aioresponse, 8, $length);
				$data = json_decode($jsonresponse);
				$adress = $data->adr;
				$type = $data->type;
				IPS_LogMessage( "Homematic Adresse:" , $address );
				$this->AddAddress($address, $type);
				$this->response = true;	
			}
		
		return $this->response;
		}
	
	//Adresse hinzuf�gen
	protected function AddAddress($address, $type)
	{
		$instance = IPS_GetInstance($this->InstanceID)["InstanceID"];
		IPS_SetProperty($instance, "HomematicAddress", $address); //Adresse setzten.
		IPS_SetProperty($instance, "HomematicType", $type); // Typ setzten.
		IPS_SetProperty($instance, "LearnAddressHomematic", false); //Haken entfernen.
		IPS_ApplyChanges($instance); //Neue Konfiguration �bernehmen
		IPS_LogMessage( "Homematic Adresse hinzugef�gt:" , $address );
		// Status aktiv
        $this->SetStatus(102);
		//Status-Variablen anlegen
		$stateId = $this->RegisterVariableBoolean("STATE", "Status", "~Switch", 1);
		$this->EnableAction("STATE");	
	}
	
	// Profil anlegen
	protected function SetupProfile($vartype, $name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $stepsize, $digits, $associations)
	{
		if (!IPS_VariableProfileExists($name))
		{
			switch ($vartype)
			{
				case IPSVarType::vtBoolean:
					
					break;
				case IPSVarType::vtInteger:
					$this->RegisterProfileIntegerAss($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $stepsize, $digits, $associations);
					break;
				case IPSVarType::vtFloat:
					$this->RegisterProfileFloatAss($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $stepsize, $digits, $associations);
					break;
				case IPSVarType::vtString:
					$this->RegisterProfileString($name, $icon);
					break;
			}	
		}
		return $name;
	}
	
	//Profile
	protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
        
    }
	
	protected function RegisterProfileIntegerAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } 
		/*
		else {
            //undefiened offset
			$MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);
        
		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }
			
	protected function RegisterProfileString($Name, $Icon)
	{
        
        if(!IPS_VariableProfileExists($Name))
			{
            IPS_CreateVariableProfile($Name, 3);
			IPS_SetVariableProfileIcon($Name, $Icon);
			} 
		else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 3)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        
        //IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        //IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }
	
	protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 2);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 2)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }
	
	protected function RegisterProfileFloatAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } 
		/*
		else {
            //undefiened offset
			$MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);
        
		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

}

class IPSVarType extends stdClass
{

    const vtNone = -1;
    const vtBoolean = 0;
    const vtInteger = 1;
    const vtFloat = 2;
    const vtString = 3;
    

}

?>