<?php
/**
 * Translation file
 * @copyright  Copyright (c) Fadzrul Aiman
 * @license     http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 * @link          https://github.com/fadzrulaiman/SKG-LMS
 * @since       0.4.7
 * @author      Ceibga Bao <info@sansin.com.tw>
 */

$lang['admin_diagnostic_title'] = '数据和设置诊断';
$lang['admin_diagnostic_description'] = '判断数据和设置问题';
$lang['admin_diagnostic_no_error'] = '没有错误';

$lang['admin_diagnostic_requests_tab'] = '请假请求';
$lang['admin_diagnostic_requests_description'] = '接受但重复的请假请求';
$lang['admin_diagnostic_requests_thead_id'] = '证号';
$lang['admin_diagnostic_requests_thead_employee'] = '员工';
$lang['admin_diagnostic_requests_thead_start_date'] = '开始日期';
$lang['admin_diagnostic_requests_thead_status'] = '职位';
$lang['admin_diagnostic_requests_thead_type'] = '编辑';

$lang['admin_diagnostic_datetype_tab'] = '上午/下午';
$lang['admin_diagnostic_datetype_description'] = '请假时间错误';
$lang['admin_diagnostic_datetype_thead_id'] = '证号';
$lang['admin_diagnostic_datetype_thead_employee'] = '员工';
$lang['admin_diagnostic_datetype_thead_start_date'] = '日期';
$lang['admin_diagnostic_datetype_thead_start_type'] = '开始';
$lang['admin_diagnostic_datetype_thead_end_type'] = '结束';
$lang['admin_diagnostic_datetype_thead_status'] = '职位';

$lang['admin_diagnostic_entitlements_tab'] = '享有类别';
$lang['admin_diagnostic_entitlements_description'] = '拥有超过一年资格的合同和员工列表';
$lang['admin_diagnostic_entitlements_thead_id'] = '证号';
$lang['admin_diagnostic_entitlements_thead_type'] = '编辑';
$lang['admin_diagnostic_entitlements_thead_name'] = '名字';
$lang['admin_diagnostic_entitlements_thead_start_date'] = '开始日期';
$lang['admin_diagnostic_entitlements_thead_end_date'] = '结束日期';
$lang['admin_diagnostic_entitlements_type_contract'] = '类别';
$lang['admin_diagnostic_entitlements_type_employee'] = '员工';
$lang['admin_diagnostic_entitlements_deletion_problem'] = '删除数据失败';

$lang['admin_diagnostic_daysoff_tab'] = '休假日';
$lang['admin_diagnostic_daysoff_description'] = '已定义非工作时段的天数（每合同）';
$lang['admin_diagnostic_daysoff_thead_id'] = '证号';
$lang['admin_diagnostic_daysoff_thead_name'] = '名字';
$lang['admin_diagnostic_daysoff_thead_ym1'] = '去年';
$lang['admin_diagnostic_daysoff_thead_y'] = '今年';
$lang['admin_diagnostic_daysoff_thead_yp1'] = '明年';

$lang['admin_diagnostic_overtime_tab'] = '加班';
$lang['admin_diagnostic_overtime_description'] = '加班请求时长为负数';
$lang['admin_diagnostic_overtime_thead_id'] = '证号';
$lang['admin_diagnostic_overtime_thead_employee'] = '员工';
$lang['admin_diagnostic_overtime_thead_date'] = '日期';
$lang['admin_diagnostic_overtime_thead_duration'] = '时段';
$lang['admin_diagnostic_overtime_thead_status'] = '职位';

$lang['admin_diagnostic_contract_tab'] = '类别';
$lang['admin_diagnostic_contract_description'] = '未使用的类别（请检查重复类别）';
$lang['admin_diagnostic_contract_thead_id'] = '证号';
$lang['admin_diagnostic_contract_thead_name'] = '名字';

$lang['admin_diagnostic_balance_tab'] = '余额';
$lang['admin_diagnostic_balance_description'] = '没有资格的请假请求';
$lang['admin_diagnostic_balance_thead_id'] = '证号';
$lang['admin_diagnostic_balance_thead_employee'] = '员工';
$lang['admin_diagnostic_balance_thead_contract'] = '类别';
$lang['admin_diagnostic_balance_thead_start_date'] = '开始日期';
$lang['admin_diagnostic_balance_thead_status'] = '职位';

$lang['admin_diagnostic_overlapping_tab'] = '重叠';
$lang['admin_diagnostic_overlapping_description'] = '在两个年度期间重叠的请假请求';
$lang['admin_diagnostic_overlapping_thead_id'] = 'ID';
$lang['admin_diagnostic_overlapping_thead_employee'] = '员工';
$lang['admin_diagnostic_overlapping_thead_contract'] = '合同';
$lang['admin_diagnostic_overlapping_thead_start_date'] = '开始日期';
$lang['admin_diagnostic_overlapping_thead_end_date'] = '结束日期';
$lang['admin_diagnostic_overlapping_thead_status'] = '状态';

$lang['admin_oauthclients_title'] = 'OAuth客户端和会话';
$lang['admin_oauthclients_tab_clients'] = '客户端';
$lang['admin_oauthclients_tab_clients_description'] = '允许使用REST API的客户端列表';
$lang['admin_oauthclients_thead_tip_edit'] = '编辑客户端';
$lang['admin_oauthclients_thead_tip_delete'] = '删除客户端';
$lang['admin_oauthclients_button_add'] = '添加';
$lang['admin_oauthclients_popup_add_title'] = '添加OAuth客户端';
$lang['admin_oauthclients_popup_select_user_title'] = '关联到一个实际用户';
$lang['admin_oauthclients_error_exists'] = '此client_id已存在';
$lang['admin_oauthclients_confirm_delete'] = '您确定要继续吗？';
$lang['admin_oauthclients_tab_sessions'] = '会话';
$lang['admin_oauthclients_tab_sessions_description'] = '活动REST API OAuth会话列表';
$lang['admin_oauthclients_button_purge'] = '清除';
