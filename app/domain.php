<?php
declare(strict_types=1);

function cents(string $value): int {
    if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $value)) throw new InvalidArgumentException('Neispravan iznos.');
    [$whole,$fraction] = array_pad(explode('.', $value), 2, '');
    return (int)$whole * 100 + (int)str_pad($fraction, 2, '0');
}
function decimal(int $value): string { return sprintf('%d.%02d', intdiv($value,100), $value%100); }
function transitions(string $status): array {
    return match($status) { 'new'=>['preparing','shipped','handed_over','cancelled'], 'preparing'=>['shipped','handed_over','cancelled'], 'shipped'=>['delivered','returned'], 'delivered'=>['returned'], 'handed_over'=>['returned'], 'completed'=>['returned'], default=>[] };
}
function orderStatus(string $status): string {
    return ['new'=>'U pripremi za slanje','preparing'=>'U pripremi za slanje','shipped'=>'Poslano','delivered'=>'Dostavljeno','handed_over'=>'Predano lično','completed'=>'Otpremljeno (stari zapis)','cancelled'=>'Otkazano','returned'=>'Vraćeno'][$status] ?? $status;
}
function isOrderSettled(array $order):bool{return in_array($order['status'],['shipped','delivered','handed_over','completed'],true)&&in_array($order['inventory_state'],['deducted','untracked'],true)&&$order['payment_status']==='paid';}
function exitReasons():array{return ['withdrawal'=>'Povučeno iz prodaje','write_off'=>'Otpis / oštećenje','personal_sale'=>'Lična prodaja i predaja','other'=>'Drugi izlaz'];}
