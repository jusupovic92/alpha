<?php
declare(strict_types=1);
function usernamesReady():bool{return (bool)query("SELECT version FROM schema_migrations WHERE version='014_usernames'")->fetchColumn();}
function requireUsernames():void {if(!usernamesReady())throw new RuntimeException('Nakon backupa primijenite database/migrations/014_usernames.sql u postojeću bazu.');}
function normalizeUsername(mixed $value):string {
    if(!is_string($value))throw new RuntimeException('Unesite korisničko ime.');$value=strtolower(trim($value));
    if(!preg_match('/^[a-z0-9][a-z0-9._-]{2,63}$/D',$value))throw new RuntimeException('Korisničko ime mora imati 3–64 znaka: slova a–z, brojeve, tačku, crticu ili donju crtu. Prvi znak mora biti slovo ili broj.');
    return $value;
}
