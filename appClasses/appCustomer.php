<?php
/**
 * Created by PhpStorm.
 * User: Developer
 * Date: 19/01/2018
 * Time: 18:55
 */

namespace app\System\Customer;
use app\dbClass\appDBClass;

class appCustomer
{
    private $dbCon;

    public function __construct()
    {
        $this->dbCon = new appDBClass();
    }

    public function appCustomerData($data = NULL)
    {
        $v_reqMethod = $data['method'];
        if($v_reqMethod == "POST")
        {
            $v_customerNomeFantasia = !empty($data['customerNomeFantasia']) ? addslashes(trim($data['customerNomeFantasia'])) : NULL;
            $v_customerRazaoSocial = !empty($data['customerRazaoSocial']) ? addslashes(trim($data['customerRazaoSocial'])) : NULL;
            $v_customerEmail = !empty($data['customerEmail']) ? addslashes(trim($data['customerEmail'])) : NULL;
            $v_customerPhone = !empty($data['customerPhone']) ? trim($data['customerPhone']) : NULL;
            $v_customerCnpj = !empty($data['customerCnpj']) ? trim($data['customerCnpj']) : NULL;
            $v_token =  hash('sha256', date("Y-m-d H:i:s").$v_customerNomeFantasia);
            if(is_null($v_customerNomeFantasia) || strlen($v_customerNomeFantasia) < 3)
            {
                $v_return['status'] = false;
            }
            else
            {
                $query = "INSERT INTO %appDBprefix%_customer_data (customer_cnpj,customer_razao_social,customer_nome_fantasia,customer_phone,customer_email,customer_token) VALUES ('".$v_customerCnpj."','".$v_customerRazaoSocial."','".$v_customerNomeFantasia."','".$v_customerPhone."','".$v_customerEmail."','".$v_token."') ";
                $v_return = $this->dbCon->dbInsert($query);
                $v_return['status'] = true;
            }
            return $v_return;
        }
        elseif ($v_reqMethod == "PUT")
        {
            $v_customerID = !empty($data['customerID']) ? addslashes(trim($data['customerID'])) : NULL;
            $v_customerNomeFantasia = !empty($data['customerNomeFantasia']) ? addslashes(trim($data['customerNomeFantasia'])) : NULL;
            $v_customerRazaoSocial = !empty($data['customerRazaoSocial']) ? addslashes(trim($data['customerRazaoSocial'])) : NULL;
            $v_customerEmail = !empty($data['customerEmail']) ? addslashes(trim($data['customerEmail'])) : NULL;
            $v_customerPhone = !empty($data['customerPhone']) ? trim($data['customerPhone']) : NULL;
            $v_customerCnpj = !empty($data['customerCnpj']) ? trim($data['customerCnpj']) : NULL;
            if(is_null($v_customerNomeFantasia) || strlen($v_customerNomeFantasia) < 3)
            {
                $v_return['status'] = false;
            }
            else
            {
                $query = "UPDATE %appDBprefix%_customer_data SET customer_cnpj = '".$v_customerCnpj."',customer_razao_social = '".$v_customerRazaoSocial."',customer_nome_fantasia = '".$v_customerNomeFantasia."',customer_phone = '".$v_customerPhone."',customer_email = '".$v_customerEmail."' WHERE customer_id = ".$v_customerID;
                //print $query;die();
                $v_return = $this->dbCon->dbUpdate($query);
                $v_return['status'] = true;
            }
            return $v_return;
        }
        elseif ($v_reqMethod == "STATUS")
        {
            $v_customerID = !empty($data['customerID']) ? $data['customerID'] : NULL;
            $v_customerStatus = !empty($data['customerStatus']) ? $data['customerStatus'] : "0";

            if(is_null($v_customerID)  || empty($v_customerID))
            {
                $v_return['status'] = false;
                echo json_encode($v_return);
            }
            else
            {
                $query = "UPDATE %appDBprefix%_customer_data SET customer_status = ".$v_customerStatus."  WHERE clnt = '".$_SESSION['userClnt']."' AND customer_id = ".$v_customerID;
                $v_return = $this->dbCon->dbUpdate($query);
                $v_return['status'] = true;
                echo json_encode($v_return);
            }
        }
        elseif ($v_reqMethod == "GET")
        {
            $v_customerID = !empty($data['customerID']) ? $data['customerID'] : NULL;
            //var_dump($v_customerID);die();
            if(is_null($v_customerID) || empty($v_customerID))
            {
                $v_return['apiData']['status'] = false;
                return $v_return;
            }
            else
            {
                $query = "SELECT customer_id,clnt,customer_group_id,customer_group_desc,customer_name,customer_nickname,customer_logo,country_id,more_information,created_at,created_by,user_name,customer_status  FROM %appDBprefix%_view_customer_list WHERE customer_id = $v_customerID AND clnt = '".$_SESSION['userClnt']."'";
                $v_return = $this->dbCon->dbSelect($query);
                return $v_return;
            }
        }
        elseif ($v_reqMethod == "DELETE")
        {
            $v_customerID = !empty($data['customerID']) ? $data['customerID'] : NULL;
            if(is_null($v_customerID) || empty($v_customerID))
            {
                $v_return['status'] = false;
                echo json_encode($v_return);
            }
            else
            {
                $query = "DELETE FROM %appDBprefix%_customer_data WHERE customer_id = $v_customerID ";
                $v_return = $this->dbCon->dbDelete($query);


                if($v_return['deleteStatus'] == true) {
                    $v_return['status'] = true;
                } else {
                    $v_return['status'] = false;
                }
                echo json_encode($v_return);
            }
        }
        elseif ($v_reqMethod == "EXISTCNPJ")
        {
            $v_customerCNPJ = !empty($data['customerCNPJ']) ? $data['customerCNPJ'] : NULL;
            //verifica se CNPJ já existe
            $query = "SELECT customer_id,customer_nickname FROM %appDBprefix%_view_customer_list WHERE customer_cnpj = '".$v_customerCNPJ."' AND clnt = '".$_SESSION['userClnt']."' LIMIT 1";
            $v_return = $this->dbCon->dbSelect($query);
            if($v_return['rsTotal'] > 0){
                $v_return['status'] = true;
                $v_return['customerID'] = $v_return['rsData'][0]['customer_id'];
                $v_return['customerNickname'] = $v_return['rsData'][0]['customer_nickname'];
            }else{
                $v_return['status'] = false;

                //Caso n exista.Busca dados do CNPJ na API
                $dataCNPJ =  $this->appCustomerGetDataByCNPJ($v_customerCNPJ);
                $v_address['cityID'] = false;
                if($dataCNPJ->return == 'OK'){

                    if($dataCNPJ->result->situacao == 'BAIXADA'){
                        $v_return['resultCNPJ'] = $dataCNPJ;
                    }else{
                        //Api retornando dados busca o city_id e state_id na nossa base de dados
                        $cityComboDesc['cityDesc'] = $dataCNPJ->result->municipio;
                        $cityComboDesc['stateCode'] = $dataCNPJ->result->uf;
                        $cityComboDesc['countryCode'] = 'BR';
                        $v_comboData = new appAddress();
                        $v_returnCombo = $v_comboData->getComboCityIDByDesc($cityComboDesc);

                        if($v_returnCombo['rsTotal'] > 0){
                            //Todos dados de endereço para cadastro
                            $v_data = $v_returnCombo['rsData'][0];
                            $v_address['cityID'] = $v_data['city_id'];
                            $v_address['cityDesc'] = $v_data['city_desc'];
                            $v_address['stateID'] = $v_data['state_id'];
                            $v_address['stateDesc'] = $v_data['state_desc'];
                            $v_address['stateCode'] = $v_data['state_code'];
                            $v_address['countryID'] = $v_data['country_id'];
                            $v_address['countryDesc'] = $v_data['country_desc'];
                            $v_address['countryCode'] = $v_data['country_code'];
                            $v_address['fullAddress'] = $dataCNPJ->result->logradouro.', '.$dataCNPJ->result->numero;
                            $v_address['complement'] = $dataCNPJ->result->complemento;
                            $v_address['zipCode'] = str_replace('.', "", $dataCNPJ->result->cep);
                            $v_return['addAddress'] = $v_address;
                        }
                    }
                }

                $v_return['resultCNPJ'] = $dataCNPJ;
            }

            echo json_encode($v_return);
        }
        else
        {
            header("HTTP/1.0 204 No Content");
        }
    }
}