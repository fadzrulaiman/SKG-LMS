<?php
/**
 * 翻译文件
 * @copyright  版权所有（c）Fadzrul Aiman
 * @license     http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 * @link          https://github.com/fadzrulaiman/SKG-LMS
 * @since       0.4.7
 * @author      Ceibga Bao <info@sansin.com.tw>
 */

$lang['leaves_summary_title'] = '我的记录';
$lang['leaves_summary_title_overtime'] = '加班明细（加到补休）';
$lang['leaves_summary_key_overtime'] = '补给';
$lang['leaves_summary_thead_type'] = '休假类别';
$lang['leaves_summary_thead_available'] = '可行';
$lang['leaves_summary_thead_taken'] = '拿取';
$lang['leaves_summary_thead_entitled'] = '可享有权利';
$lang['leaves_summary_thead_description'] = '描述';
$lang['leaves_summary_thead_actual'] = '实际';
$lang['leaves_summary_thead_simulated'] = '模拟';
$lang['leaves_summary_tbody_empty'] = '此时段无可休假天数，请联系HR部门/管理者';
$lang['leaves_summary_flash_msg_error'] = '你无类别，请联系HR部门/管理者';
$lang['leaves_summary_date_field'] = '报告建立日期';

$lang['leaves_index_title'] = '我的休假申请';
$lang['leaves_index_thead_tip_view'] = '预览';
$lang['leaves_index_thead_tip_edit'] = '编辑';
$lang['leaves_index_thead_tip_cancel'] = '取消';
$lang['leaves_index_thead_tip_delete'] = '删除';
$lang['leaves_index_thead_tip_history'] = '显示历史';
$lang['leaves_index_thead_id'] = '证号';
$lang['leaves_index_thead_start_date'] = '开始日期';
$lang['leaves_index_thead_end_date'] = '结束日期';
$lang['leaves_index_thead_cause'] = '理由';
$lang['leaves_index_thead_duration'] = '时段';
$lang['leaves_index_thead_type'] = '编辑';
$lang['leaves_index_thead_status'] = '职位';
$lang['leaves_index_thead_requested_date'] = 'Requested';
$lang['leaves_index_thead_last_change'] = '最后更改';
$lang['leaves_index_button_export'] = '导出此单';
$lang['leaves_index_button_create'] = '新申请';
$lang['leaves_index_popup_delete_title'] = '删除休假申请';
$lang['leaves_index_popup_delete_message'] = '你可以删除一休假申请，但无法再做恢复';
$lang['leaves_index_popup_delete_question'] = '你要继续吗？';
$lang['leaves_index_popup_delete_button_yes'] = '是';
$lang['leaves_index_popup_delete_button_no'] = '否';

$lang['leaves_history_thead_changed_date'] = '更改日期';
$lang['leaves_history_thead_change_type'] = '更改类型';
$lang['leaves_history_thead_changed_by'] = '更改者';
$lang['leaves_history_thead_start_date'] = '开始日期';
$lang['leaves_history_thead_end_date'] = '结束日期';
$lang['leaves_history_thead_cause'] = '理由';
$lang['leaves_history_thead_duration'] = '时段';
$lang['leaves_history_thead_type'] = '类型';
$lang['leaves_history_thead_status'] = '状态';

$lang['leaves_create_title'] = '提交休假申请';
$lang['leaves_create_field_start'] = '开始日期';
$lang['leaves_create_field_end'] = '结束日期';
$lang['leaves_create_field_type'] = '休假类别';
$lang['leaves_create_field_duration'] = '时段';
$lang['leaves_create_field_duration_message'] = '你已超出可使用天数';
$lang['leaves_create_field_overlapping_message'] = '你已有申请同一天休假';
$lang['leaves_create_field_cause'] = '原因（可不填）';
$lang['leaves_create_field_status'] = '职位';
$lang['leaves_create_button_create'] = '申请休假';
$lang['leaves_create_button_cancel'] = '取消';
$lang['leaves_create_flash_msg_success'] = '休假申请已建立成功';
$lang['leaves_create_flash_msg_error'] = '休假申请已建立或更新，但尚未被同意';

$lang['leaves_flash_spn_list_days_off'] = '%s非工作日于此时段';
$lang['leaves_flash_msg_overlap_dayoff'] = '你的休假申请符合非工作日';

$lang['leaves_cancellation_flash_msg_error'] = '你无法取消这个休假申请';
$lang['leaves_cancellation_flash_msg_success'] = '取消请求已成功发送';
$lang['requests_cancellation_accept_flash_msg_success'] = '休假请求已成功取消';
$lang['requests_cancellation_accept_flash_msg_error'] = '尝试接受取消时出现错误';
$lang['requests_cancellation_reject_flash_msg_success'] = '休假请求现在恢复到原始状态';
$lang['requests_cancellation_reject_flash_msg_error'] = '尝试拒绝取消时出现错误';

$lang['leaves_edit_html_title'] = '编辑一休假申请';
$lang['leaves_edit_title'] = '编辑休假申请';
$lang['leaves_edit_field_start'] = '开始日期';
$lang['leaves_edit_field_end'] = '结束日期';
$lang['leaves_edit_field_type'] = '休假类别';
$lang['leaves_edit_field_duration'] = '时段';
$lang['leaves_edit_field_duration_message'] = '你已超出可使用天数';
$lang['leaves_edit_field_cause'] = '原因（可不填）';
$lang['leaves_edit_field_status'] = '职位';
$lang['leaves_edit_button_update'] = '更新休假';
$lang['leaves_edit_button_cancel'] = '取消';
$lang['leaves_edit_flash_msg_error'] = '你无法编辑已提交的休假申请';
$lang['leaves_edit_flash_msg_success'] = '休假申请已成功更新';

$lang['leaves_validate_mandatory_js_msg'] = '“字段” + fieldname + “是必填项。”';
$lang['leaves_validate_flash_msg_no_contract'] = '你无类别，请联系HR部门/管理者';
$lang['leaves_validate_flash_msg_overlap_period'] = '你无法同时建立2个休假申请，请分别建立';

$lang['leaves_cancel_flash_msg_error'] = '你无法取消这个休假申请';
$lang['leaves_cancel_flash_msg_success'] = '休假请求已成功取消';
$lang['leaves_cancel_unauthorized_msg_error'] = '你无法取消开始日期在过去的休假申请，请向您的经理请求拒绝。';

$lang['leaves_delete_flash_msg_error'] = '你无法删除这个休假申请';
$lang['leaves_delete_flash_msg_success'] = '休假申请已成功删除';

$lang['leaves_view_title'] = '预览休假申请';
$lang['leaves_view_html_title'] = '预览一休假申请';
$lang['leaves_view_field_start'] = '开始日期';
$lang['leaves_view_field_end'] = '结束日期';
$lang['leaves_view_field_type'] = '休假类别';
$lang['leaves_view_field_duration'] = '时段';
$lang['leaves_view_field_cause'] = '理由';
$lang['leaves_view_field_status'] = '职位';
$lang['leaves_view_button_edit'] = '编辑';
$lang['leaves_view_button_back_list'] = '返回列表';

$lang['leaves_export_title'] = '休假列表';
$lang['leaves_export_thead_id'] = '证号';
$lang['leaves_export_thead_start_date'] = '开始日期';
$lang['leaves_export_thead_start_date_type'] = '上午/下午';
$lang['leaves_export_thead_end_date'] = '结束日期';
$lang['leaves_export_thead_end_date_type'] = '上午/下午';
$lang['leaves_export_thead_cause'] = '理由';
$lang['leaves_export_thead_duration'] = '时段';
$lang['leaves_export_thead_type'] = '类型';
$lang['leaves_export_thead_status'] = '状态';

$lang['leaves_button_send_reminder'] = '发送提醒';
$lang['leaves_reminder_flash_msg_success'] = '提醒邮件已发送至经理';

$lang['leaves_comment_title'] = '评论';
$lang['leaves_comment_new_comment'] = '新评论';
$lang['leaves_comment_send_comment'] = '发送评论';
$lang['leaves_comment_author_saying'] = '说';
$lang['leaves_comment_status_changed'] = '假期状态已更改为';
$lang['Identifier'] = '标识符';
$lang['First Name'] = '名';
$lang['Last Name'] = '姓';
$lang['Date Hired'] = '入职日期';
$lang['Department'] = '部门';
$lang['Position'] = '职位';
$lang['Contract'] = '合同';
$lang['Annual Leave'] = '年假';
$lang['Sick Leave'] = '病假';
$lang['Leave Bank'] = '休假银行';
$lang['Leave Duration'] = '休假时长';
$lang['Total Days'] = '总天数';
$lang['Weekend & Public Holiday Days'] = '周末与公共假日天数';
$lang['Work Days'] = '工作日';
