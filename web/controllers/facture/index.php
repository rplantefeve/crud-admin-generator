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

$app->match('/facture/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'factureId', 
		'date', 
		'client_id', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `facture`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `facture`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'client_id'){
			    $findexternal_sql = 'SELECT `denomination` FROM `client` WHERE `clientId` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['denomination'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
			}


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});

$app->match('/facture', function () use ($app) {
    
	$table_columns = array(
		'factureId', 
		'date', 
		'client_id', 

    );

    $primary_key = "factureId";	

    return $app['twig']->render('facture/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('facture_list');



$app->match('/facture/create', function () use ($app) {
    
    $initial_data = array(
		'factureId' => '', 
		'date' => '', 
		'client_id' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `clientId`, `denomination` FROM `client`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['clientId']] = $findexternal_row['denomination'];
	}
	if(count($options) > 0){
	    $form = $form->add('client_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('client_id', 'text', array('required' => true));
	}



	$form = $form->add('factureId', 'text', array('required' => true));
	$form = $form->add('date', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `facture` (`factureId`, `date`, `client_id`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['factureId'], $data['date'], $data['client_id']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'facture créé(e) !',
                )
            );
            return $app->redirect($app['url_generator']->generate('facture_list'));

        }
    }

    return $app['twig']->render('facture/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('facture_create');



$app->match('/facture/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `facture` WHERE `factureId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Enregistrement non trouvé !',
            )
        );        
        return $app->redirect($app['url_generator']->generate('facture_list'));
    }

    
    $initial_data = array(
		'factureId' => $row_sql['factureId'], 
		'date' => $row_sql['date'], 
		'client_id' => $row_sql['client_id'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `clientId`, `denomination` FROM `client`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['clientId']] = $findexternal_row['denomination'];
	}
	if(count($options) > 0){
	    $form = $form->add('client_id', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('client_id', 'text', array('required' => true));
	}


	$form = $form->add('factureId', 'text', array('required' => true));
	$form = $form->add('date', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `facture` SET `factureId` = ?, `date` = ?, `client_id` = ? WHERE `factureId` = ?";
            $app['db']->executeUpdate($update_query, array($data['factureId'], $data['date'], $data['client_id'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'facture modifié(e) !',
                )
            );
            return $app->redirect($app['url_generator']->generate('facture_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('facture/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('facture_edit');



$app->match('/facture/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `facture` WHERE `factureId` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `facture` WHERE `factureId` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'facture supprimé(e) !',
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

    return $app->redirect($app['url_generator']->generate('facture_list'));

})
->bind('facture_delete');






