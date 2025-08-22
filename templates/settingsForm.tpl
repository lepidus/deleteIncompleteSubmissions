<script>
    $(function() {ldelim}
    $('#deleteIncompleteSubmissionsSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="plnSettings">
    <div id="description">
        <p>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.description"}</p>
        <p>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.title"}</p>
        <ul>
            <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.one"}</li>
            <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.two"}</li>
        </ul>
    </div>
    <br>
    <form class="pkp_form" id="deleteIncompleteSubmissionsSettingsForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="deletion" save=true}">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="deleteIncompleteSubmissionsSettingsFormNotification"}

        {fbvFormArea id="deleteIncompleteSubmissionsSettingsFormArea"}

        {fbvFormSection list="true"}
        {fbvElement type="select" name="deletionThreshold" id="deletionThreshold" from=$thresholdValues selected=$defaultThreshold label="plugins.generic.deleteIncompleteSubmissions.deletion.threshold" translate="0" size=$fbvStyles.size.SMALL}

        {fbvFormButtons id="deleteIncompleteSubmissionsSettingsFormSubmit" submitText="common.delete"}

        {/fbvFormSection}

        {/fbvFormArea}

    </form>
</div>