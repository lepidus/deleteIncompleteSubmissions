<script>
    $(function() {ldelim}
    $('#deleteIncompleteSubmissionsSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="plnSettings">
    <div id="description">{translate key="plugins.generic.deleteIncompleteSubmissions.multipleDeletion.description"}
    </div>
    <br>
    <form class="pkp_form" id="deleteIncompleteSubmissionsSettingsForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="multipleDeletion" save=true}">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="deleteIncompleteSubmissionsSettingsFormNotification"}

        {fbvFormArea id="deleteIncompleteSubmissionsSettingsFormArea"}

        {fbvFormSection list="true"}
        {fbvElement type="select" name="deletionThreshold" id="deletionThreshold" from=$thresholdValues label="plugins.generic.deleteIncompleteSubmissions.multipleDeletion.deletionThreshold" translate="0" size=$fbvStyles.size.SMALL}

        {fbvFormButtons id="deleteIncompleteSubmissionsSettingsFormSubmit" submitText="common.delete"}

        {/fbvFormSection}

        {/fbvFormArea}

    </form>
</div>