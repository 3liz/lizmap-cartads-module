<parcelles>
{foreach $parcelles as $parcelle}
    <parcelle>
        <nom>{$parcelle->nom}</nom>
        <surface>{$parcelle->surface}</surface>
        <contenance>{$parcelle->contenance}</contenance>
        <occupation>{$parcelle->occupation}</occupation>
        <zones>
        {foreach $parcelle->zones as $zone}
            <zone>
                <nom>{$zone->nom}</nom>
                <pourcentage>{$zone->pourcentage}</pourcentage>
                <surface>{$zone->surface}</surface>
                <type>
                    <nom>{$zone->type_nom}</nom>
                    <code>{$zone->type_code}</code>
                </type>
                <observation>{$zone->observation}</observation>
            </zone>
        {/foreach}
        </zones>
    </parcelle>
{/foreach}
</parcelles>
