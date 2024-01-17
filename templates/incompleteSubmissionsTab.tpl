<tab id="incompleteSubmissions" label="{translate key="submissions.incomplete"}" :badge="components.incompleteSubmissions.itemsMax">
    <submissions-list-panel
        v-bind="components.incompleteSubmissions"
        @set="set"
    >
        <template v-slot:item="{ldelim}item{rdelim}">
            <incomplete-submissions-list-item
            :key="item.id"
            :item="item"
            :apiUrl="components.incompleteSubmissions.apiUrl"
            :infoUrl="components.incompleteSubmissions.infoUrl"
            :assignParticipantUrl="components.incompleteSubmissions.assignParticipantUrl"
            @addFilter="components.incompleteSubmissions.addFilter"
            ></incomplete-submissions-list-item>
        </template>
    </submission-list-panel>
    
</tab>
