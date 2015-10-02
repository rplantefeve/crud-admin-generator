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

$app->match('/ligne/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ligneId', 
		'qte', 
		'nature', 
		'description', 
		'prixUnitaire', 
		'facture_factureId', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `ligne`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `ligne`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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

$app->match('/ligne', function () use ($app) {
    
	$table_columns = array(
		'ligneId', 
		'qte', 
		'nature', 
		'description', 
		'prixUnitaire', 
		'facture_factureId', 

    );

    $primary_key = "ligneId";	

    return $app['twig']->render('ligne/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('ligne_list');



$app->match('/ligne/create', function () use ($app) {
    
    $initial_data = array(
		'qte' => '', 
		'nature' => '', 
		'description' => '', 
		'prixUnitaire' => '', 
		'facture_factureId' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('qte', 'text', array('required' => true));
	$form = $form->add('nature', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => true));
	$form = $form->add('prixUnitaire', 'text', array('required' => true));
	$form = $form->add('facture_factureId', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `ligne` (`qte`, `nature`, `description`, `prixUnitaire`, `facture_factureId`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['qte'], $data['nature'], $data['description'], $data['prixUnitaire'], $data['facture_factureId']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'ligne créé(e) !',
                )
            );
            return $app->redirect($app['url_generator']->generate('ligne_list'));

        }
    }

    return $app['twig']->render('ligne/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('ligne_create');



$app->match('/ligne/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `ligne` WHERE `ligneId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Enregistrement non trouvé !',
            )
        );        
        return $app->redirect($app['url_generator']->generate('ligne_list'));
    }

    
    $initial_data = array(
		'qte' => $row_sql['qte'], 
		'nature' => $row_sql['nature'], 
		'description' => $row_sql['description'], 
		'prixUnitaire' => $row_sql['prixUnitaire'], 
		'facture_factureId' => $row_sql['facture_factureId'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('qte', 'text', array('required' => true));
	$form = $form->add('nature', 'text', array('required' => true));
	$form = $form->add('description', 'textarea', array('required' => true));
	$form = $form->add('prixUnitaire', 'text', array('required' => true));
	$form = $form->add('facture_factureId', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `ligne` SET `qte` = ?, `nature` = ?, `description` = ?, `prixUnitaire` = ?, `facture_factureId` = ? WHERE `ligneId` = ?";
            $app['db']->executeUpdate($update_query, array($data['qte'], $data['nature'], $data['description'], $data['prixUnitaire'], $data['facture_factureId'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'ligne modifié(e) !',
                )
            );
            return $app->redirect($app['url_generator']->generate('ligne_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('ligne/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('ligne_edit');



$app->match('/ligne/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `ligne` WHERE `ligneId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `ligne` WHERE `ligneId` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'ligne supprimé(e) !',
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

    return $app->redirect($app['url_generator']->generate('ligne_list'));

})
->bind('ligne_delete');






