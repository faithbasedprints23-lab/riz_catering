$routes->get('/', 'EventBuilder::index');
$routes->get('/menu', 'Menu::index');
$routes->post('/menu/submitOrder', 'Menu::submitOrder');
$routes->get('/ownerdashboard', 'OwnerDashboard::index');

// Form submissions
$routes->post('/ownerdashboard/addMenuItem', 'OwnerDashboard::addMenuItem');
$routes->post('/ownerdashboard/recordPayment/(:num)', 'OwnerDashboard::recordPayment/$1');
$routes->post('/ownerdashboard/updateStatus/(:num)', 'OwnerDashboard::updateOrderStatus/$1');