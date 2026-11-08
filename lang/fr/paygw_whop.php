<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for paygw_whop (French).
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['apikey'] = 'Clé API';
$string['apikey_help'] = 'Une clé API d\'entreprise, dans la page Développeur de votre tableau de bord Whop. Le bac à sable et la production ont des clés distinctes.';
$string['back'] = 'Retour';
$string['canceled'] = 'Le paiement a été annulé. Rien n\'a été débité.';
$string['checkagain'] = 'Vérifier à nouveau';
$string['companyid'] = 'Identifiant d\'entreprise';
$string['companyid_help'] = 'L\'identifiant de votre entreprise Whop, qui commence par biz_. Il figure dans l\'adresse de votre tableau de bord Whop.';
$string['continue'] = 'Continuer vers le paiement';
$string['environment'] = 'Environnement';
$string['environment:live'] = 'Production';
$string['environment:sandbox'] = 'Bac à sable (test)';
$string['environment_help'] = 'Le bac à sable utilise l\'environnement de test de Whop, sans mouvement d\'argent ; la carte de test 4242 4242 4242 4242 y fonctionne. Passez en production avec vos clés de production.';
$string['error:api'] = 'Whop : {$a}';
$string['error:busy'] = 'Ce paiement est en cours de vérification. Réessayez dans un instant.';
$string['error:companyid'] = 'Saisissez l\'identifiant de votre entreprise Whop, qui commence par biz_.';
$string['error:currency'] = 'Whop ne peut pas encaisser dans cette devise.';
$string['error:generic'] = '{$a}';
$string['error:owner'] = 'Le paiement est passé, mais {$a} n\'a pas pu l\'enregistrer. Contactez l\'administrateur du site.';
$string['error:required'] = 'Obligatoire pour activer Whop.';
$string['error:webhooksecret'] = 'Saisissez le secret de signature du webhook Whop. Il commence par ws_.';
$string['event:paymentfailed'] = 'Paiement Whop échoué';
$string['event:paymentreceived'] = 'Paiement Whop reçu';
$string['failed'] = 'Le paiement n\'a pas abouti. Vous pouvez réessayer.';
$string['gatewaydescription'] = 'Cartes, Apple Pay, Google Pay, PayPal et plus, via Whop.';
$string['gatewayname'] = 'Carte ou portefeuille (Whop)';
$string['method:whop'] = 'Carte, Apple Pay, Google Pay ou PayPal';
$string['paid'] = 'Paiement reçu. Merci.';
$string['paywith'] = 'Payer avec Whop';
$string['pluginname'] = 'Whop';
$string['pluginname_desc'] = 'Encaissez les paiements de cours d\'acheteurs du monde entier via Whop, qui agit comme marchand officiel.';
$string['privacy:metadata:paygw_whop'] = 'Les paiements Whop lancés sur ce site.';
$string['privacy:metadata:paygw_whop:amount'] = 'Le montant demandé.';
$string['privacy:metadata:paygw_whop:checkoutid'] = 'L\'identifiant du paiement chez Whop.';
$string['privacy:metadata:paygw_whop:status'] = 'Si le paiement est en attente, payé, échoué ou annulé.';
$string['privacy:metadata:paygw_whop:timecreated'] = 'La date de début du paiement.';
$string['privacy:metadata:paygw_whop:userid'] = 'La personne qui paie.';
$string['privacy:metadata:whop'] = 'Pour encaisser un paiement, le montant, la devise, le nom de l\'achat et une référence sont envoyés à Whop. Le payeur saisit ses informations de paiement sur la page de Whop, pas dans Moodle.';
$string['privacy:metadata:whop:amount'] = 'Le montant à payer.';
$string['privacy:metadata:whop:description'] = 'Ce qui est payé.';
$string['privacy:metadata:whop:metadata'] = 'Une référence vers ce paiement dans Moodle.';
$string['redirectnote'] = 'Vous payez sur la page sécurisée de Whop, puis vous revenez ici.';
$string['waiting'] = 'Whop n\'a pas encore confirmé ce paiement. Cela prend généralement quelques secondes.';
$string['webhooksecret'] = 'Secret du webhook';
$string['webhooksecret_help'] = 'Dans le tableau de bord Whop, créez un webhook vers l\'adresse ci-dessous avec les événements de paiement, puis collez son secret de signature (ws_...).';
$string['webhookurl'] = 'URL du webhook';
$string['webhookurl_help'] = 'Créez un webhook vers cette adresse dans le tableau de bord Whop (Développeur, Créer un webhook), avec les événements payment.succeeded et payment.failed.';
