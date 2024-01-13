<script>
    $(function() {ldelim}
    $('#deleteIncompleteSubmissionsSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="plnSettings">
    <div id="description">{translate key="plugins.generic.deleteIncompleteSubmissions.settings.description"}</div>
    <br>
    <form class="pkp_form" id="deleteIncompleteSubmissionsSettingsForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="toggleRequiredMetadataSettingsFormNotification"}

        {fbvFormArea id="deleteIncompleteSubmissionsSettingsFormArea"}

        {fbvFormSection list="true"}

        {fbvElement type="input" name="daysPast" id="daysPast" label="plugins.generic.deleteIncompleteSubmissions.settings.daysPast"}
        <div class="sub_label"> days past<div>
        <br>

        {fbvFormButtons id="deleteIncompleteSubmissionsSettingsFormSubmit" submitText="common.save"}
        {/fbvFormArea}
    </form>
</div>