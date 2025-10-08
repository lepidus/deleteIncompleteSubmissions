<script>
    $(function() {ldelim}
    $('#deleteIncompleteSubmissionsSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="plnSettings">
    <form class="pkp_form" id="deleteIncompleteSubmissionsSettingsForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="deletion" save=true}"
    >
        {csrf}

        <div id="description">
            <p>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.description"}</p>
            <p>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.title"}</p>
            <ul>
                <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.one"}</li>
                <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.two"}</li>
            </ul>
        </div>
        <br>

        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="deleteIncompleteSubmissionsSettingsFormNotification"}

        {fbvFormArea id="deleteIncompleteSubmissionsSettingsFormArea"}
            {fbvFormSection}
                <div>
                    <label for="deletionThreshold" style="display: grid; grid-template-columns: 8fr 1fr 2fr; column-gap: 10px;">
                        <p style="grid-row: 1;">{translate key="plugins.generic.deleteIncompleteSubmissions.deletionLabelPrefix"}</p>
                        <input style="grid-row: 1; margin-block-start: 10px;" type="number" id="deletionThreshold" name="deletionThreshold" value="{$defaultThreshold|escape}" min="1" step="1" required/>
                        <p style="grid-row: 1;">{translate key="plugins.generic.deleteIncompleteSubmissions.deletionLabelSuffix"}</p>
                    </label>
                </div>

                {fbvFormButtons id="deleteIncompleteSubmissionsSettingsFormSubmit" submitText="common.delete"}
            {/fbvFormSection}
        {/fbvFormArea}
    </form>
</div>