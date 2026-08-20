<script>
    $(function() {ldelim}
    var $form = $('#deleteIncompleteSubmissionsSettingsForm');
    $form.pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    $form.find('[data-deletion-action]').on('click', function() {ldelim}
        $form.find('[name="deletionAction"]').val($(this).data('deletion-action'));
    {rdelim});
    {rdelim});
</script>

<div id="deleteIncompleteSubmissionsSettings">
    <form class="pkp_form" id="deleteIncompleteSubmissionsSettingsForm" method="post"
        action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="deletion" save=true}"
    >
        {csrf}
        <input type="hidden" name="deletionAction" value="preview">
        {if $isPreview}
            <input type="hidden" name="previewId" value="{$previewId|escape}">
        {/if}

        <div id="description">
            <p>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.description"}</p>
            <p>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.title"}</p>
            <ul>
                <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.one"}</li>
                <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.two"}</li>
                <li>{translate key="plugins.generic.deleteIncompleteSubmissions.deletion.warning.item.three"}</li>
            </ul>
        </div>
        <br>

        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="deleteIncompleteSubmissionsSettingsFormNotification"}

        {fbvFormArea id="deleteIncompleteSubmissionsSettingsFormArea"}
            {fbvFormSection}
                {if $errors.deletionThreshold}
                    <span class="error" id="deletionThresholdError">{$errors.deletionThreshold|escape}</span>
                {/if}
                <div>
                    <label for="deletionThreshold" style="display: grid; grid-template-columns: 8fr 1fr 2fr; column-gap: 10px;">
                        <p style="grid-row: 1;">{translate key="plugins.generic.deleteIncompleteSubmissions.deletionLabelPrefix"}</p>
                        <input style="grid-row: 1; margin-block-start: 10px;" type="number" id="deletionThreshold" name="deletionThreshold" value="{$deletionThreshold|default:$defaultThreshold|escape}" min="1" step="1" required{if $errors.deletionThreshold} aria-invalid="true" aria-describedby="deletionThresholdError"{/if}/>
                        <p style="grid-row: 1;">{translate key="plugins.generic.deleteIncompleteSubmissions.deletionLabelSuffix"}</p>
                    </label>
                </div>

                {if $isPreview}
                    <h3>{translate key="plugins.generic.deleteIncompleteSubmissions.preview.title"}</h3>
                    {if $previewSubmissions|@count}
                        <table class="pkpTable">
                            <thead>
                                <tr>
                                    <th scope="col">{translate key="plugins.generic.deleteIncompleteSubmissions.preview.id"}</th>
                                    <th scope="col">{translate key="plugins.generic.deleteIncompleteSubmissions.preview.submissionTitle"}</th>
                                    <th scope="col">{translate key="plugins.generic.deleteIncompleteSubmissions.preview.status"}</th>
                                    <th scope="col">{translate key="plugins.generic.deleteIncompleteSubmissions.preview.lastActivity"}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$previewSubmissions item=submission}
                                    <tr>
                                        <td>{$submission.id|escape}</td>
                                        <td>{$submission.title|escape}</td>
                                        <td>{$submission.status|escape}</td>
                                        <td>{$submission.dateLastActivity|escape}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                        <p>{translate key="plugins.generic.deleteIncompleteSubmissions.preview.confirmation"}</p>
                    {else}
                        <p>{translate key="plugins.generic.deleteIncompleteSubmissions.preview.empty"}</p>
                    {/if}
                {/if}

                <div class="pkp_form_buttons">
                    <button class="pkp_button" type="submit" data-deletion-action="preview">
                        {translate key="plugins.generic.deleteIncompleteSubmissions.preview.action"}
                    </button>
                    {if $isPreview && $previewSubmissions|@count}
                        <button class="pkp_button submitFormButton" type="submit" data-deletion-action="confirm">
                            {translate key="plugins.generic.deleteIncompleteSubmissions.preview.deleteConfirmed"}
                        </button>
                    {/if}
                </div>
            {/fbvFormSection}
        {/fbvFormArea}
    </form>
</div>
