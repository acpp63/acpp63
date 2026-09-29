<?php
// Formulaire de contact ACPP avec pièces jointes — hébergement mutualisé OVH (fonction mail()).
$destinataire = 'contact@acpp63.fr';
$expediteur   = 'no-reply@acpp63.fr';   // adresse technique du domaine

const MAX_FICHIERS = 5;
const MAX_TOTAL    = 10485760;          // 10 Mo au total
$extensions_ok = ['pdf','jpg','jpeg','png','webp','heic','gif','svg','ai','eps',
                  'dxf','dwg','step','stp','igs','iges','stl',
                  'doc','docx','xls','xlsx','odt','ods','zip'];

function retour($page){ header('Location: ' . $page); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') retour('contact.html');
if (!empty($_POST['site'])) retour('merci.html');                 // piège à robots

function propre($v){ return trim(str_replace(["\r","\n"], ' ', strip_tags($v ?? ''))); }
$nom     = propre($_POST['nom'] ?? '');
$societe = propre($_POST['societe'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$tel     = propre($_POST['tel'] ?? '');
$univers = propre($_POST['univers'] ?? '');
$message = trim(strip_tags($_POST['message'] ?? ''));
if ($nom === '' || !$email || $message === '' || mb_strlen($message) > 5000) retour('erreur.html');

// ---- Pièces jointes
$pj = []; $total = 0;
if (!empty($_FILES['fichiers']) && is_array($_FILES['fichiers']['name'])) {
  $n = count($_FILES['fichiers']['name']);
  for ($i = 0; $i < $n; $i++) {
    $err = $_FILES['fichiers']['error'][$i];
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) retour('erreur.html');
    $tmp  = $_FILES['fichiers']['tmp_name'][$i];
    $taille = (int)$_FILES['fichiers']['size'][$i];
    if (!is_uploaded_file($tmp)) retour('erreur.html');
    $original = basename($_FILES['fichiers']['name'][$i]);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions_ok, true)) retour('erreur.html');
    $total += $taille;
    if (count($pj) >= MAX_FICHIERS || $total > MAX_TOTAL) retour('erreur.html');
    $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($original, PATHINFO_FILENAME));
    $nomfic = substr(trim($base, '_') ?: 'fichier', 0, 80) . '.' . $ext;
    $pj[] = ['nom' => $nomfic, 'chemin' => $tmp, 'taille' => $taille];
  }
}

// ---- Construction de l'e-mail
$sujet = '=?UTF-8?B?' . base64_encode("Demande site acpp63.fr - $univers - $nom") . '?=';
$corps = "Nouvelle demande depuis le site acpp63.fr\n\n"
       . "Nom : $nom\nSociété : $societe\nE-mail : $email\nTéléphone : $tel\nConcerne : $univers\n\n"
       . "Message :\n$message\n";
if ($pj) {
  $corps .= "\nPièces jointes (" . count($pj) . ") :\n";
  foreach ($pj as $f) $corps .= "- {$f['nom']} (" . round($f['taille']/1024) . " Ko)\n";
}

$limite = 'acpp_' . bin2hex(random_bytes(12));
$entetes = "From: ACPP site web <$expediteur>\r\n"
         . "Reply-To: $email\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: multipart/mixed; boundary=\"$limite\"\r\n";

$contenu = "--$limite\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "Content-Transfer-Encoding: base64\r\n\r\n"
         . chunk_split(base64_encode($corps)) . "\r\n";
foreach ($pj as $f) {
  $contenu .= "--$limite\r\n"
            . "Content-Type: application/octet-stream; name=\"{$f['nom']}\"\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"{$f['nom']}\"\r\n\r\n"
            . chunk_split(base64_encode(file_get_contents($f['chemin']))) . "\r\n";
}
$contenu .= "--$limite--\r\n";

$ok = mail($destinataire, $sujet, $contenu, $entetes, "-f$expediteur");
retour($ok ? 'merci.html' : 'erreur.html');
