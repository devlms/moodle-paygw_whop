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
 * Strings for paygw_whop (Arabic).
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['apikey'] = 'مفتاح API';
$string['apikey_help'] = 'مفتاح API للشركة من صفحة المطوّر في لوحة تحكم Whop. لبيئة الاختبار وللإنتاج مفاتيح منفصلة.';
$string['back'] = 'رجوع';
$string['canceled'] = 'أُلغيت عملية الدفع. لم يُقتطع أي مبلغ.';
$string['checkagain'] = 'تحقّق مرة أخرى';
$string['companyid'] = 'معرّف الشركة';
$string['companyid_help'] = 'معرّف شركتك على Whop، ويبدأ بـ biz_. تجده في رابط لوحة تحكم Whop.';
$string['continue'] = 'متابعة إلى الدفع';
$string['environment'] = 'البيئة';
$string['environment:live'] = 'الإنتاج';
$string['environment:sandbox'] = 'بيئة الاختبار';
$string['environment_help'] = 'تستخدم بيئة الاختبار خوادم Whop التجريبية حيث لا تتحرك أي أموال، وتعمل فيها بطاقة الاختبار 4242 4242 4242 4242. انتقل إلى الإنتاج بمفاتيح الإنتاج.';
$string['error:api'] = 'Whop: {$a}';
$string['error:busy'] = 'يجري التحقق من هذه الدفعة الآن. حاول مرة أخرى بعد لحظات.';
$string['error:companyid'] = 'أدخل معرّف شركتك على Whop، ويبدأ بـ biz_.';
$string['error:currency'] = 'لا يقبل Whop الدفع بهذه العملة.';
$string['error:generic'] = '{$a}';
$string['error:owner'] = 'تمّت عملية الدفع، لكن {$a} لم يتمكّن من تسجيلها. اتصل بمدير الموقع.';
$string['error:required'] = 'مطلوب لتفعيل Whop.';
$string['error:webhooksecret'] = 'أدخل سرّ توقيع الويب هوك من Whop، ويبدأ بـ ws_.';
$string['event:paymentfailed'] = 'فشل دفع Whop';
$string['event:paymentreceived'] = 'استُلمت دفعة Whop';
$string['failed'] = 'لم تنجح عملية الدفع. يمكنك المحاولة مرة أخرى.';
$string['gatewaydescription'] = 'البطاقات و Apple Pay و Google Pay و PayPal وغيرها عبر Whop.';
$string['gatewayname'] = 'بطاقة أو محفظة (Whop)';
$string['method:whop'] = 'بطاقة أو Apple Pay أو Google Pay أو PayPal';
$string['paid'] = 'تمّ استلام الدفعة. شكرًا لك.';
$string['paywith'] = 'الدفع عبر Whop';
$string['pluginname'] = 'Whop';
$string['pluginname_desc'] = 'استقبل مدفوعات الدورات من مشترين في أي مكان عبر Whop الذي يعمل بصفته التاجر الرسمي.';
$string['privacy:metadata:paygw_whop'] = 'عمليات الدفع عبر Whop التي بدأت في هذا الموقع.';
$string['privacy:metadata:paygw_whop:amount'] = 'المبلغ المطلوب.';
$string['privacy:metadata:paygw_whop:checkoutid'] = 'معرّف عملية الدفع لدى Whop.';
$string['privacy:metadata:paygw_whop:status'] = 'حالة الدفع: قيد الانتظار أو مدفوعة أو فاشلة أو ملغاة.';
$string['privacy:metadata:paygw_whop:timecreated'] = 'تاريخ بدء عملية الدفع.';
$string['privacy:metadata:paygw_whop:userid'] = 'الشخص الذي يدفع.';
$string['privacy:metadata:whop'] = 'لإتمام الدفع تُرسَل إلى Whop قيمةُ المبلغ والعملةُ واسمُ ما يُدفع مقابله ومرجعٌ للعملية. يُدخل الدافع بيانات الدفع في صفحة Whop وليس في Moodle.';
$string['privacy:metadata:whop:amount'] = 'المبلغ المطلوب دفعه.';
$string['privacy:metadata:whop:description'] = 'ما يُدفع مقابله.';
$string['privacy:metadata:whop:metadata'] = 'مرجع لهذه الدفعة في Moodle.';
$string['productname'] = 'مدفوعات مقررات Moodle';
$string['redirectnote'] = 'تدفع في صفحة Whop الآمنة ثم تعود إلى هنا.';
$string['waiting'] = 'لم يؤكّد Whop هذه الدفعة بعد. يستغرق ذلك عادة بضع ثوانٍ.';
$string['webhooksecret'] = 'سرّ الويب هوك';
$string['webhooksecret_help'] = 'في لوحة تحكم Whop، أنشئ ويب هوك للعنوان أدناه مع أحداث الدفع، ثم الصق سرّ التوقيع (ws_...).';
$string['webhookurl'] = 'رابط الويب هوك';
$string['webhookurl_help'] = 'أنشئ ويب هوك لهذا العنوان في لوحة تحكم Whop (المطوّر، إنشاء ويب هوك) مع الحدثين payment.succeeded و payment.failed.';
