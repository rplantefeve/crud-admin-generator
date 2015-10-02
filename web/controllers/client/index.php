<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/client/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'clientId', 
		'denomination', 
		'adresse', 
		'codePostal', 
		'ville', 

    );
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `client`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `client`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});

$app->match('/client', function () use ($app) {
    
	$table_columns = array(
		'clientId', 
		'denomination', 
		'adresse', 
		'codePostal', 
		'ville', 

    );

    $primary_key = "clientId";	

    return $app['twig']->render('client/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('client_list');



$app->match('/client/create', function () use ($app) {
    
    $initial_data = array(
		'denomination' => '', 
		'adresse' => '', 
		'codePostal' => '', 
		'ville' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('denomination', 'text', array('required' => true));
	$form = $form->add('adresse', 'text', array('required' => true));
	$form = $form->add('codePostal', 'text', array('required' => true));
	$form = $form->add('ville', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `client` (`denomination`, `adresse`, `codePostal`, `ville`) VALUES (?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['denomination'], $data['adresse'], $data['codePostal'], $data['ville']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'client créé(e) !',
                )
            );
            return $app->redirect($app['url_generator']->generate('client_list'));

        }
    }

    return $app['twig']->render('client/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('client_create');



$app->match('/client/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `client` WHERE `clientId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Enregistrement non trouvé !',
            )
        );        
        return $app->redirect($app['url_generator']->generate('client_list'));
    }

    
    $initial_data = array(
		'denomination' => $row_sql['denomination'], 
		'adresse' => $row_sql['adresse'], 
		'codePostal' => $row_sql['codePostal'], 
		'ville' => $row_sql['ville'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('denomination', 'text', array('required' => true));
	$form = $form->add('adresse', 'text', array('required' => true));
	$form = $form->add('codePostal', 'text', array('required' => true));
	$form = $form->add('ville', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `client` SET `denomination` = ?, `adresse` = ?, `codePostal` = ?, `ville` = ? WHERE `clientId` = ?";
            $app['db']->executeUpdate($update_query, array($data['denomination'], $data['adresse'], $data['codePostal'], $data['ville'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'client modifié(e) !',
                )
            );
            return $app->redirect($app['url_generator']->generate('client_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('client/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('client_edit');



$app->match('/client/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `client` WHERE `clientId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `client` WHERE `clientId` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'client supprimé(e) !',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Enregistrement non trouvé !',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('client_list'));

})
->bind('client_delete');






