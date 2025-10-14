<?php
require_once 'application/config/database.php';

$CI =& get_instance();
$CI->load->database();

$query = $CI->db->select('l.id as loan_id, c.dni, c.first_name, c.last_name, li.id as quota_id, li.fee_amount')
    ->from('loans l')
    ->join('customers c', 'l.customer_id = c.id')
    ->join('loan_items li', 'li.loan_id = l.id')
    ->where('li.status', 1)
    ->limit(5)
    ->get();

$result = $query->result();
echo "Datos de prueba para simulación:\n";
foreach ($result as $row) {
    echo "Loan ID: {$row->loan_id}, DNI: {$row->dni}, Nombre: {$row->first_name} {$row->last_name}, Quota ID: {$row->quota_id}, Monto: {$row->fee_amount}\n";
}

// También obtener un user_id
$user_query = $CI->db->select('id, first_name, last_name')->from('users')->limit(1)->get();
$user = $user_query->row();
echo "\nUsuario para pago: ID: {$user->id}, Nombre: {$user->first_name} {$user->last_name}\n";
?>