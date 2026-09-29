<?php
// Formulaire de contact ACPP — fonctionne sur l'hébergement mutualisé OVH (fonction mail()).
// Destinataire : modifier ici si besoin.
$destinataire = 'contact@acpp63.fr';
// Expéditeur technique : doit être une adresse du domaine (OVH refuse souvent un expéditeur extérieur).
$expediteur   = 'no-reply@acpp63.fr';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: contact.html'); exit; }
if (!empty($_POST['site'])) { header('Location: merci.html'); exit; } // piège à robots

function propre($v){ return trim(str_replace(["\r","\n"], ' ', strip_tags($v ?? ''))); }
$nom     = propre($_POST['nom'] ?? '');
$societe = propre($_POST['societe'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$tel     = propre($_POST['tel'] ?? '');
$univers = propre($_POST['univers'] ?? '');
$message = trim(strip_tags($_POST['message'] ?? ''));

if ($nom === '' || !$email || $message === '' || mb_strlen($message) > 5000) { header('Location: erreur.html'); exit; }

$sujet = '=?UTF-8?B?' . base64_encode("Demande site acpp63.fr - $univers - $nom") . '?=';
$corps = "Nouvelle demande depuis le site acpp63.fr\n\n"
       . "Nom : $nom\nSociété : $societe\nE-mail : $email\nTéléphone : $tel\nConcerne : $univers\n\n"
       . "Message :\n$message\n";
$entetes = "From: ACPP site web <$expediteur>\r\n"
         . "Reply-To: $email\r\n"
         . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

$ok = mail($destinataire, $sujet, $corps, $entetes, "-f$expediteur");
header('Location: ' . ($ok ? 'merci.html' : 'erreur.html'));
exit;
