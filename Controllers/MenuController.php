<?php
namespace Controllers;

use Models\MenuItem;

class MenuController {
    public function index() {
        $menuModel = new MenuItem();
        $items = $menuModel->getAllAvailable();
        
        echo json_encode(['data' => $items]);
    }
}
