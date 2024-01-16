<tab id="incompleteSubmissions" label="{translate key="submissions.incomplete"}" :badge="components.incompleteSubmissions.itemsMax">
    <submissions-list-panel
        v-bind="components.incompleteSubmissions"
        @set="set"
    ></submission-list-panel>
</tab>
