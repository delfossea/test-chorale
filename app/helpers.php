<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
function flash(string $type, ?string $message = null): ?string { if ($message !== null) { $_SESSION['flash'] = [$type,$message]; return null; } $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f ? '<div class="flash '.h($f[0]).'">'.h($f[1]).'</div>' : null; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Requête expirée.'); } }
function current_user(): ?array { static $user = false; if ($user !== false) return $user; if (empty($_SESSION['user_id'])) return $user = null; $s=db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$_SESSION['user_id']]); return $user=$s->fetch() ?: null; }
function require_user(): array { $u=current_user(); if (!$u || $u['status'] !== 'active') { flash('error','Veuillez vous connecter.'); redirect('?page=login'); } return $u; }
function require_admin(): array { $u=require_user(); if ($u['role'] !== 'admin') { http_response_code(403); exit('Accès réservé aux administrateurs.'); } return $u; }
function token(): string { return bin2hex(random_bytes(32)); }
function token_hash(string $token): string { return hash('sha256',$token); }
function valid_token(string $table, string $token): ?array { $s=db()->prepare("SELECT * FROM $table WHERE token_hash=? AND expires_at > ".sql_now().' AND ' . ($table==='invitations' ? "status='pending'" : 'used_at IS NULL') . ' ORDER BY id DESC LIMIT 1'); $s->execute([token_hash($token)]); return $s->fetch() ?: null; }
function approximate(?float $number): ?float { return $number === null ? null : round($number, 2); }
function geocode(string $address): ?array { if (trim($address)==='') return null; $url='https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q='.rawurlencode($address); $ctx=stream_context_create(['http'=>['header'=>"User-Agent: ChoraleCarpool/1.0\r\n",'timeout'=>4]]); $json=@file_get_contents($url,false,$ctx); $data=$json ? json_decode($json,true) : []; return !empty($data[0]) ? ['lat'=>(float)$data[0]['lat'],'lon'=>(float)$data[0]['lon']] : null; }
function layout(string $title, string $content): void { $u=current_user(); $nav=$u ? '<a href="?page=directory">Annuaire</a><a href="?page=profile">Mon profil</a><a href="?page=requests">Mes demandes</a>'.($u['role']==='admin'?'<a href="?page=admin">Administration</a>':'').'<a href="?page=logout">Déconnexion</a>' : '<a href="?page=login">Connexion</a>'; echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' — '.APP_NAME.'</title><link rel="stylesheet" href="assets/style.css"></head><body><header><a class="brand" href="?">'.APP_NAME.'</a><nav>'.$nav.'</nav></header><main>'.(flash()??'').$content.'</main></body></html>'; }
function input(string $name, string $label, string $value='', string $type='text', bool $required=false): string { return '<label>'.$label.'<input type="'.$type.'" name="'.$name.'" value="'.h($value).'" '.($required?'required':'').'></label>'; }
