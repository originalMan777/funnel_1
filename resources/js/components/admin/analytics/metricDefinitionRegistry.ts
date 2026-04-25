export type MetricDefinitionContext = {
    clusterKey?: string | null;
    clusterLabel?: string | null;
    subClusterKey?: string | null;
    subClusterLabel?: string | null;
    metricGroupKey?: string | null;
    metricGroupLabel?: string | null;
};

export type MetricDefinitionMetricLike = {
    key?: string | null;
    label?: string | null;
    description?: string | null;
    formula?: string | null;
    whyItMatters?: string | null;
    affects?: string[] | null;
};

export type MetricDefinition = {
    definition: string;
    meaning: string;
    formula?: string;
    whyItMatters: string;
    affects: string[];
    operatorGuidance: string;
};

type MetricDefinitionEntry = {
    aliases: string[];
    definition: MetricDefinition;
};

type SubClusterDefinitionScope = {
    clusterAliases: string[];
    subClusterAliases: string[];
    metrics: MetricDefinitionEntry[];
};

const normalizeToken = (value?: string | null) =>
    (value ?? '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();

const containsTokenSequence = (fingerprint: string, alias: string) => {
    const fingerprintTokens = fingerprint.split(' ').filter(Boolean);
    const aliasTokens = alias.split(' ').filter(Boolean);

    if (aliasTokens.length === 0 || aliasTokens.length > fingerprintTokens.length) {
        return false;
    }

    for (let start = 0; start <= fingerprintTokens.length - aliasTokens.length; start += 1) {
        const matches = aliasTokens.every(
            (token, index) => fingerprintTokens[start + index] === token,
        );

        if (matches) {
            return true;
        }
    }

    return false;
};

const matchesAliases = (fingerprint: string, aliases: string[]) =>
    aliases.some((alias) => {
        const normalizedAlias = normalizeToken(alias);

        return (
            fingerprint === normalizedAlias ||
            containsTokenSequence(fingerprint, normalizedAlias)
        );
    });

const definition = (
    definitionText: string,
    meaning: string,
    whyItMatters: string,
    affects: string[],
    operatorGuidance: string,
    formula?: string,
): MetricDefinition => ({
    definition: definitionText,
    meaning,
    formula,
    whyItMatters,
    affects,
    operatorGuidance,
});

const scopedDefinitions: SubClusterDefinitionScope[] = [
    {
        clusterAliases: ['traffic'],
        subClusterAliases: ['pages', 'page'],
        metrics: [
            {
                aliases: ['views', 'page views'],
                definition: definition(
                    'Total number of tracked page views attributed to the page.',
                    'Measures page visibility and traffic volume.',
                    'Helps operators understand which pages are being reached often enough to matter and which ones may need stronger traffic sources or navigation support.',
                    [
                        'traffic source quality',
                        'site navigation',
                        'internal linking',
                        'search visibility',
                        'campaign distribution',
                    ],
                    'Higher view volume usually suggests the page is receiving more exposure, while lower view volume usually points toward discoverability or traffic allocation questions. Operators should pair it with conversion metrics before deciding whether a page needs more traffic or a stronger experience.',
                ),
            },
            {
                aliases: ['conversions', 'page conversions'],
                definition: definition(
                    'Number of conversions associated with visits to the page.',
                    'Measures outcome volume connected to page traffic.',
                    'Shows which pages are contributing to real business outcomes instead of only attracting visits.',
                    [
                        'traffic intent',
                        'page clarity',
                        'offer relevance',
                        'trust signals',
                        'next-step design',
                    ],
                    'Higher conversion volume usually suggests the page is contributing to outcomes, while lower volume should be interpreted in the context of both traffic and conversion rate so operators do not mistake low traffic for poor page effectiveness.',
                ),
            },
            {
                aliases: ['conversion rate', 'page conversion rate'],
                definition: definition(
                    'Conversions divided by page views.',
                    'Measures how effectively the page turns visits into outcomes.',
                    'Helps separate high-traffic pages that merely attract visitors from pages that actually move users forward.',
                    [
                        'message-to-intent match',
                        'page clarity',
                        'offer strength',
                        'call-to-action quality',
                        'friction after the page visit',
                    ],
                    'A higher page conversion rate usually suggests the page is aligning well with visitor intent, while a lower rate usually points toward message mismatch, weak next steps, or post-visit friction.',
                    'Conversions / Views',
                ),
            },
            {
                aliases: [
                    'avg view to conversion',
                    'average view to conversion',
                    'median view to conversion',
                    'view to conversion',
                ],
                definition: definition(
                    'Average or median elapsed time from page interaction to conversion.',
                    'Measures how quickly the page leads into a completed outcome.',
                    'Reveals whether the path after visiting the page is smooth or whether users are hesitating before converting.',
                    [
                        'offer clarity',
                        'follow-up complexity',
                        'number of required steps',
                        'user confidence',
                        'timing of the next action',
                    ],
                    'Shorter elapsed time usually suggests the page leads cleanly into the next action, while longer elapsed time usually points operators toward friction, delayed follow-up, or a weaker page-to-conversion bridge.',
                ),
            },
            {
                aliases: ['page path', 'page label'],
                definition: definition(
                    'The page identifier or display label used to group page-level analytics.',
                    'Provides the page context behind the metric values in this modal.',
                    'Helps operators know exactly which page experience the metrics belong to before making changes or routing more traffic.',
                    [
                        'URL structure',
                        'page naming conventions',
                        'content taxonomy',
                        'report grouping logic',
                    ],
                    'Operators should use the page label or path to confirm they are reviewing the intended experience before interpreting the supporting metrics. It is a context field, not a performance score.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['traffic'],
        subClusterAliases: ['ctas', 'cta'],
        metrics: [
            {
                aliases: ['impressions', 'cta impressions'],
                definition: definition(
                    'Number of times the CTA was shown.',
                    'Measures CTA exposure.',
                    'Helps separate visibility problems from persuasion problems.',
                    [
                        'page traffic',
                        'CTA placement',
                        'display logic',
                        'audience targeting',
                    ],
                    'Higher impressions usually indicate stronger exposure, while lower impressions usually suggest a visibility, targeting, or placement issue. Operators should use this metric to understand reach before judging click quality.',
                ),
            },
            {
                aliases: ['clicks', 'cta clicks', 'cta_clicks'],
                definition: definition(
                    'Total number of times a CTA was clicked.',
                    'Measures attention and initial user intent.',
                    'Shows which CTAs attract users and which calls-to-action are earning interaction.',
                    [
                        'CTA placement',
                        'CTA wording',
                        'CTA contrast/design',
                        'page traffic volume',
                    ],
                    'Higher click volume usually suggests the CTA is visible and compelling enough to prompt action. Lower click volume usually points operators toward placement, copy, contrast, or traffic quality before drawing performance conclusions.',
                ),
            },
            {
                aliases: ['ctr', 'click through rate', 'clickthrough rate'],
                definition: definition(
                    'Clicks divided by impressions.',
                    'Measures how effective the CTA is when users see it.',
                    'Shows whether the CTA message, placement, and design are convincing enough to earn action.',
                    [
                        'offer clarity',
                        'CTA copy',
                        'design hierarchy',
                        'placement',
                        'audience intent',
                    ],
                    'A higher CTR usually suggests the CTA is resonating once seen, while a lower CTR usually points toward message clarity, hierarchy, or audience-fit questions. It should be read alongside impressions so visibility and persuasion are not conflated.',
                    'Clicks / Impressions',
                ),
            },
            {
                aliases: ['conversions', 'cta conversions'],
                definition: definition(
                    'Number of users who completed the desired action after interacting with the CTA.',
                    'Measures actual business outcome.',
                    'Connects CTA interaction to real lead or sales outcomes.',
                    [
                        'offer quality',
                        'destination page',
                        'form friction',
                        'trust signals',
                        'follow-up flow',
                    ],
                    'Higher conversion volume usually suggests the CTA and its downstream experience are producing outcomes, while lower conversion volume calls for checking both click volume and post-click friction before assuming the CTA itself is the problem.',
                ),
            },
            {
                aliases: ['conversion rate', 'cta conversion rate', 'conversion_rate'],
                definition: definition(
                    'Conversions divided by CTA clicks.',
                    'Measures post-click follow-through quality.',
                    'Shows whether the CTA is attracting the right people and sending them into a flow that converts.',
                    [
                        'CTA-to-page message match',
                        'form complexity',
                        'trust signals',
                        'urgency',
                        'clarity of next step',
                    ],
                    'A higher conversion rate usually suggests strong alignment between the CTA and the next step, while a lower rate usually points operators toward message match, friction, and trust after the click.',
                    'Conversions / Clicks',
                ),
            },
            {
                aliases: ['avg time to click', 'average time to click', 'time to click'],
                definition: definition(
                    'Average time it takes a user to click after seeing or entering the CTA context.',
                    'Measures decision speed.',
                    'Helps identify whether the CTA is immediately clear or causing hesitation.',
                    [
                        'message clarity',
                        'CTA placement',
                        'page complexity',
                        'user intent',
                    ],
                    'Shorter time to click usually suggests the CTA is quickly understandable, while longer elapsed time usually signals hesitation, competing distractions, or unclear placement. It should be interpreted as a friction signal rather than a verdict on overall performance.',
                ),
            },
            {
                aliases: [
                    'time to conversion',
                    'avg click to conversion',
                    'average click to conversion',
                    'median click to conversion',
                ],
                definition: definition(
                    'Average or median time from CTA click to completed conversion.',
                    'Measures friction after the click.',
                    'Reveals whether the post-click process is smooth or creating hesitation.',
                    [
                        'form length',
                        'number of steps',
                        'user confidence',
                        'offer clarity',
                        'follow-up friction',
                    ],
                    'Shorter time to conversion usually suggests a smoother post-click path, while longer elapsed time usually points toward added friction, hesitation, or extra steps after the CTA interaction.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['capture', 'lead capture'],
        subClusterAliases: ['lead boxes', 'lead box', 'lead_boxes'],
        metrics: [
            {
                aliases: ['impressions', 'lead box impressions'],
                definition: definition(
                    'Number of times the lead box was shown.',
                    'Measures lead box exposure.',
                    'Helps operators distinguish weak visibility from weak form follow-through.',
                    [
                        'page traffic',
                        'placement',
                        'display rules',
                        'audience fit',
                    ],
                    'Higher impressions usually mean the lead box is being surfaced often enough to evaluate, while lower impressions usually point toward visibility or routing issues before any submission conclusions are made.',
                ),
            },
            {
                aliases: ['clicks', 'lead box clicks'],
                definition: definition(
                    'Number of times users engaged with the lead box entry point.',
                    'Measures initial interaction with the lead capture surface.',
                    'Shows whether the lead box is attracting interest beyond simple exposure.',
                    [
                        'placement',
                        'headline clarity',
                        'visual prominence',
                        'offer relevance',
                    ],
                    'Higher click volume usually suggests the lead box is drawing attention, while lower click volume usually points operators toward presentation, copy, or audience-fit questions.',
                ),
            },
            {
                aliases: ['submissions', 'lead box submissions', 'lead submissions'],
                definition: definition(
                    'Number of successful submissions attributed to the lead box.',
                    'Measures completed lead capture outcomes.',
                    'Shows whether the lead box is producing actual leads rather than only interest.',
                    [
                        'offer quality',
                        'form friction',
                        'field count',
                        'trust signals',
                        'traffic intent',
                    ],
                    'Higher submission volume usually suggests the lead box is turning attention into leads, while lower submission volume should be reviewed alongside impressions, clicks, and failures before deciding what to change.',
                ),
            },
            {
                aliases: ['failures', 'failure count'],
                definition: definition(
                    'Number of failed lead capture attempts attributed to the lead box.',
                    'Measures breakdowns in the submission process.',
                    'Helps operators spot whether the form or its supporting flow is losing users after they start engaging.',
                    [
                        'validation friction',
                        'technical errors',
                        'field complexity',
                        'session stability',
                        'integration reliability',
                    ],
                    'Higher failure counts usually suggest friction or reliability issues in the submission path, while lower failure counts generally suggest the flow is cleaner once users begin completing it.',
                ),
            },
            {
                aliases: ['submission rate', 'capture rate'],
                definition: definition(
                    'Successful submissions divided by lead box clicks.',
                    'Measures how effectively lead box engagement turns into completed submissions.',
                    'Shows whether the lead box is only attracting interest or actually converting that interest into leads.',
                    [
                        'form length',
                        'copy-to-form match',
                        'trust signals',
                        'required effort',
                        'traffic intent',
                    ],
                    'A higher submission rate usually suggests the lead box experience is converting interested users cleanly, while a lower rate usually points operators toward post-click or in-form friction.',
                    'Submissions / Clicks',
                ),
            },
            {
                aliases: ['failure rate'],
                definition: definition(
                    'Failed lead capture attempts divided by total lead box attempts.',
                    'Measures how often the lead box flow breaks down instead of completing.',
                    'Helps separate weak offer response from technical or validation failure inside the form experience.',
                    [
                        'validation rules',
                        'integration reliability',
                        'form complexity',
                        'device/browser conditions',
                    ],
                    'A higher failure rate usually points toward friction or reliability issues in the submission process, while a lower rate usually suggests the flow is stable once users attempt to submit.',
                ),
            },
            {
                aliases: ['average submission time', 'avg submission time'],
                definition: definition(
                    'Average elapsed time from lead box engagement to successful submission.',
                    'Measures how long it takes users to complete the lead box flow.',
                    'Reveals whether the capture experience feels fast and simple or slow and effortful.',
                    [
                        'field count',
                        'step count',
                        'copy clarity',
                        'device usability',
                        'user confidence',
                    ],
                    'Shorter submission time usually suggests a simpler capture path, while longer elapsed time usually points operators toward friction, confusion, or extra effort inside the flow.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['capture', 'lead capture'],
        subClusterAliases: ['popups', 'popup'],
        metrics: [
            {
                aliases: ['eligible'],
                definition: definition(
                    'Number of visits or sessions that met the rules required to show the popup.',
                    'Measures popup eligibility before display.',
                    'Helps operators understand whether popup opportunity is limited by targeting rules or by what happens after the popup appears.',
                    [
                        'display triggers',
                        'targeting rules',
                        'audience segmentation',
                        'page traffic',
                    ],
                    'Higher eligibility usually means the popup has more chances to appear, while lower eligibility usually points operators toward trigger logic or targeting scope rather than popup creative alone.',
                ),
            },
            {
                aliases: ['impressions', 'popup impressions'],
                definition: definition(
                    'Number of times the popup was shown.',
                    'Measures actual popup exposure.',
                    'Helps separate limited display opportunity from poor user response after the popup appears.',
                    [
                        'eligibility rules',
                        'trigger timing',
                        'page traffic',
                        'suppression logic',
                    ],
                    'Higher impressions usually mean the popup is being surfaced often enough to evaluate, while lower impressions usually indicate routing, trigger, or suppression constraints.',
                ),
            },
            {
                aliases: ['opens', 'open count'],
                definition: definition(
                    'Number of popup opens recorded after the popup was shown.',
                    'Measures initial popup engagement.',
                    'Shows whether the popup is prompting users to interact instead of only being seen.',
                    [
                        'offer relevance',
                        'trigger timing',
                        'design prominence',
                        'headline clarity',
                    ],
                    'Higher opens usually suggest the popup is drawing attention once displayed, while lower opens usually point toward message, timing, or relevance questions.',
                ),
            },
            {
                aliases: ['dismissals'],
                definition: definition(
                    'Number of times users dismissed the popup without completing its intended action.',
                    'Measures popup rejection.',
                    'Helps operators understand whether the popup is being treated as an interruption rather than a useful offer.',
                    [
                        'trigger timing',
                        'offer relevance',
                        'design intrusiveness',
                        'audience intent',
                    ],
                    'Higher dismissals usually suggest the popup is creating resistance or arriving at the wrong moment, while lower dismissals usually indicate less explicit rejection.',
                ),
            },
            {
                aliases: ['submissions', 'popup submissions', 'lead submissions'],
                definition: definition(
                    'Number of successful submissions recorded from the popup.',
                    'Measures lead capture outcomes generated by the popup.',
                    'Shows whether the popup produces leads or simply interrupts the session.',
                    [
                        'offer quality',
                        'form friction',
                        'timing',
                        'trust signals',
                        'audience intent',
                    ],
                    'Higher submission volume usually suggests the popup is producing useful outcomes, while lower submission volume should be read alongside impressions, opens, and dismissals before judging the popup itself.',
                ),
            },
            {
                aliases: ['open rate'],
                definition: definition(
                    'Popup opens divided by popup impressions.',
                    'Measures how often exposure turns into popup interaction.',
                    'Helps operators understand whether the popup is interesting enough to move users past initial exposure.',
                    [
                        'trigger timing',
                        'offer clarity',
                        'visual hierarchy',
                        'audience relevance',
                    ],
                    'A higher open rate usually suggests the popup is compelling once seen, while a lower rate usually points toward timing, relevance, or presentation issues.',
                    'Opens / Impressions',
                ),
            },
            {
                aliases: ['dismissal rate'],
                definition: definition(
                    'Dismissals divided by popup impressions or opens, depending on reporting design.',
                    'Measures how often the popup results in explicit rejection.',
                    'Helps operators identify whether the popup experience is creating more interruption than interest.',
                    [
                        'trigger aggressiveness',
                        'offer relevance',
                        'frequency controls',
                        'audience intent',
                    ],
                    'A higher dismissal rate usually suggests stronger resistance to the popup, while a lower rate usually indicates less direct rejection. Operators should interpret it alongside opens and submissions.',
                ),
            },
            {
                aliases: ['submission rate', 'capture rate'],
                definition: definition(
                    'Successful popup submissions divided by popup opens or other qualifying interactions, depending on reporting design.',
                    'Measures how effectively popup engagement turns into lead capture.',
                    'Shows whether the popup experience converts interest into completed submissions.',
                    [
                        'form length',
                        'offer strength',
                        'message clarity',
                        'trust signals',
                        'post-open friction',
                    ],
                    'A higher submission rate usually suggests the popup converts interest efficiently, while a lower rate usually points toward friction after the popup is opened.',
                ),
            },
            {
                aliases: ['avg open to submit', 'average open to submit', 'average submission time'],
                definition: definition(
                    'Average elapsed time from popup open to popup submission.',
                    'Measures how quickly users complete the popup flow once engaged.',
                    'Reveals whether the popup capture path is smooth or introducing hesitation after open.',
                    [
                        'form complexity',
                        'copy clarity',
                        'field count',
                        'user confidence',
                        'mobile usability',
                    ],
                    'Shorter elapsed time usually suggests a smoother popup flow, while longer elapsed time usually points operators toward confusion, friction, or excessive effort after the popup opens.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['flow'],
        subClusterAliases: ['funnels', 'funnel'],
        metrics: [
            {
                aliases: ['conversion count', 'conversions'],
                definition: definition(
                    'Number of conversions recorded inside the funnel.',
                    'Measures completed outcomes produced by the funnel path.',
                    'Shows whether the funnel is generating successful completions, not just activity at the top.',
                    [
                        'entry volume',
                        'step clarity',
                        'drop-off friction',
                        'offer relevance',
                        'technical stability',
                    ],
                    'Higher conversion count usually suggests the funnel is carrying more users through to the end state, while lower count should be interpreted alongside completion rate and drop-off before drawing conclusions.',
                ),
            },
            {
                aliases: ['completion rate'],
                definition: definition(
                    'Conversions divided by funnel entrants.',
                    'Measures how effectively users who enter the funnel reach the end state.',
                    'Helps operators understand whether the funnel is smooth enough to carry users through all required steps.',
                    [
                        'step count',
                        'step clarity',
                        'required effort',
                        'technical reliability',
                        'message continuity',
                    ],
                    'A higher completion rate usually suggests the funnel is carrying users through with less leakage, while a lower rate usually points toward friction, confusion, or weak step-to-step continuity.',
                    'Conversion Count / Funnel Entrants',
                ),
            },
            {
                aliases: ['top drop off', 'top drop-off', 'top drop off loss', 'top drop-off loss'],
                definition: definition(
                    'Largest observed loss between two steps in the funnel.',
                    'Measures where the funnel leaks the most users.',
                    'Helps operators focus improvement work on the point of highest abandonment instead of diffusing effort across every step.',
                    [
                        'step complexity',
                        'message mismatch',
                        'technical issues',
                        'loading delay',
                        'unexpected effort',
                    ],
                    'A larger top drop-off usually suggests a specific step is creating the most resistance, while a smaller top drop-off usually indicates losses are more distributed or less severe.',
                ),
            },
            {
                aliases: ['average elapsed', 'avg elapsed'],
                definition: definition(
                    'Average elapsed time across the funnel path where timing is available.',
                    'Measures how long users take to move through the funnel.',
                    'Helps operators detect whether the funnel feels quick and smooth or slow and effortful.',
                    [
                        'step count',
                        'form length',
                        'decision complexity',
                        'technical performance',
                        'handoff friction',
                    ],
                    'Shorter elapsed time usually suggests a smoother funnel path, while longer elapsed time usually points toward hesitation, extra effort, or slow transitions between steps.',
                ),
            },
            {
                aliases: ['step count'],
                definition: definition(
                    'Number of tracked steps represented in the funnel.',
                    'Measures funnel complexity at a structural level.',
                    'Gives operators context for how much process the user is expected to complete before converting.',
                    [
                        'funnel design',
                        'required information',
                        'handoff structure',
                    ],
                    'More steps usually mean more opportunity for friction or abandonment, while fewer steps usually reduce complexity. This metric is context for the funnel, not a quality score by itself.',
                ),
            },
            {
                aliases: ['dismissed without submit'],
                definition: definition(
                    'Number of popup or modal funnel interactions that were dismissed without reaching submission.',
                    'Measures abandonment inside supported dismissible funnel experiences.',
                    'Helps operators understand whether users are explicitly backing out before finishing the funnel.',
                    [
                        'popup timing',
                        'offer relevance',
                        'perceived effort',
                        'trust signals',
                        'interruptiveness',
                    ],
                    'Higher dismissed-without-submit counts usually suggest the funnel experience is being rejected before completion, while lower counts usually indicate less explicit abandonment in supported flows.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['behavior'],
        subClusterAliases: ['scenarios', 'scenario'],
        metrics: [
            {
                aliases: ['sessions', 'scenario sessions'],
                definition: definition(
                    'Number of sessions assigned to the scenario.',
                    'Measures how active the scenario pattern is.',
                    'Shows which user behavior patterns occur often enough to matter in the product experience.',
                    [
                        'traffic mix',
                        'scenario assignment logic',
                        'entry behavior',
                        'site navigation',
                    ],
                    'Higher session volume usually suggests the behavior pattern is common, while lower volume usually indicates a narrower or less frequent journey. It should be paired with outcome metrics before prioritization decisions are made.',
                ),
            },
            {
                aliases: ['converted sessions'],
                definition: definition(
                    'Number of scenario sessions that produced at least one conversion.',
                    'Measures how many sessions within the scenario resulted in an outcome.',
                    'Helps operators distinguish between busy scenarios and scenarios that actually produce conversions.',
                    [
                        'traffic intent',
                        'journey quality',
                        'scenario fit',
                        'downstream friction',
                    ],
                    'Higher converted-session counts usually suggest the scenario is contributing to outcomes, while lower counts usually call for comparing volume against conversion rate before drawing conclusions.',
                ),
            },
            {
                aliases: ['conversion total'],
                definition: definition(
                    'Total number of conversions produced by sessions in the scenario.',
                    'Measures total outcome volume generated by the scenario.',
                    'Shows which behavior patterns contribute the most conversion activity, even when one session can produce more than one result.',
                    [
                        'session quality',
                        'repeat conversion behavior',
                        'journey depth',
                        'offer relevance',
                    ],
                    'Higher conversion totals usually suggest the scenario is producing more outcome activity, while lower totals should be interpreted alongside session volume and conversion rate.',
                ),
            },
            {
                aliases: ['conversion rate'],
                definition: definition(
                    'Converted sessions divided by scenario sessions.',
                    'Measures how often the scenario produces a conversion.',
                    'Helps operators identify which behavior patterns are simply active and which ones are meaningfully outcome-oriented.',
                    [
                        'user intent',
                        'journey clarity',
                        'friction level',
                        'scenario-to-offer fit',
                    ],
                    'A higher scenario conversion rate usually suggests the behavior pattern aligns with outcomes, while a lower rate usually points toward active but less productive journeys.',
                    'Converted Sessions / Sessions',
                ),
            },
            {
                aliases: ['average events', 'avg events'],
                definition: definition(
                    'Average number of tracked events recorded inside sessions assigned to the scenario.',
                    'Measures engagement depth within the scenario.',
                    'Helps operators see whether a scenario reflects short, simple journeys or more involved behavior patterns.',
                    [
                        'journey complexity',
                        'interaction design',
                        'content depth',
                        'session intent',
                    ],
                    'Higher average events usually suggest deeper or more involved journeys, while lower average events usually suggest a simpler or shorter interaction pattern. It should be read alongside conversion results, not as a goal on its own.',
                ),
            },
            {
                aliases: ['average session duration', 'avg session duration'],
                definition: definition(
                    'Average event-based duration for sessions assigned to the scenario.',
                    'Measures how long the typical scenario session lasts.',
                    'Helps operators understand whether a scenario tends to be brief and decisive or extended and involved.',
                    [
                        'journey complexity',
                        'content depth',
                        'friction',
                        'user intent',
                    ],
                    'Longer average session duration can suggest deeper engagement or added friction, while shorter duration can suggest efficient progress or light interaction. Operators should interpret it alongside events and conversion metrics.',
                ),
            },
            {
                aliases: ['median session duration'],
                definition: definition(
                    'Median event-based duration for sessions assigned to the scenario.',
                    'Measures the midpoint session length for the scenario.',
                    'Provides a steadier read on session duration when outlier sessions could distort the average.',
                    [
                        'journey complexity',
                        'user intent',
                        'friction concentration',
                        'session consistency',
                    ],
                    'Median duration helps operators understand the more typical session length. If it diverges from the average, that usually suggests some sessions are much longer or much shorter than the rest.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['results', 'conversions'],
        subClusterAliases: ['conversions', 'conversion'],
        metrics: [
            {
                aliases: ['total conversions', 'conversions'],
                definition: definition(
                    'Total number of conversions recorded in the selected scope.',
                    'Measures overall outcome volume.',
                    'Shows what outcomes are actually happening and which conversion types dominate the reported results.',
                    [
                        'traffic quality',
                        'offer demand',
                        'conversion tracking coverage',
                        'funnel effectiveness',
                    ],
                    'Higher total conversions usually indicate more outcome activity in the selected scope, while lower totals should be read with traffic and timing context before deciding what changed.',
                ),
            },
            {
                aliases: ['share of conversions'],
                definition: definition(
                    'Share of all recorded conversions represented by the current conversion type or slice.',
                    'Measures how dominant this outcome is within the total conversion mix.',
                    'Helps operators see which outcomes are leading the result set and which ones are more secondary.',
                    [
                        'mix of conversion types',
                        'offer priorities',
                        'traffic intent',
                        'tracking completeness',
                    ],
                    'A higher share usually means this conversion type is more dominant in the overall outcome mix, while a lower share usually means it is a smaller contributor. It should be interpreted as composition, not quality.',
                    'Current Conversion Slice / Total Conversions',
                ),
            },
            {
                aliases: ['average time to conversion', 'avg time to conversion', 'time to conversion'],
                definition: definition(
                    'Average elapsed time from session start to first recorded conversion where timing is available.',
                    'Measures how quickly outcomes happen.',
                    'Helps operators understand whether conversions tend to happen quickly or require a longer path before completion.',
                    [
                        'decision complexity',
                        'step count',
                        'offer clarity',
                        'follow-up delay',
                        'user confidence',
                    ],
                    'Shorter average time to conversion usually suggests a faster route to outcome, while longer elapsed time usually points toward more complex or slower-moving conversion paths.',
                ),
            },
            {
                aliases: ['median time to conversion'],
                definition: definition(
                    'Median elapsed time from session start to first recorded conversion where timing is available.',
                    'Measures the midpoint conversion timing for the selected scope.',
                    'Provides a more typical timing read when a few unusually fast or slow conversions could skew the average.',
                    [
                        'journey consistency',
                        'step count',
                        'user confidence',
                        'follow-up timing',
                    ],
                    'Median time helps operators understand the more typical conversion pace. If it differs sharply from the average, that usually suggests outlier conversion paths are present.',
                ),
            },
            {
                aliases: ['conversion type'],
                definition: definition(
                    'The cataloged conversion category used to group recorded outcomes.',
                    'Provides the type context behind the conversion metrics.',
                    'Helps operators understand which kind of outcome the modal is describing before comparing totals or timing.',
                    [
                        'conversion taxonomy',
                        'tracking implementation',
                        'report grouping logic',
                    ],
                    'Conversion type is a classification field rather than a performance signal. Operators should use it to confirm the outcome category they are reviewing.',
                ),
            },
        ],
    },
    {
        clusterAliases: ['source'],
        subClusterAliases: ['attribution'],
        metrics: [
            {
                aliases: ['attributed conversions'],
                definition: definition(
                    'Number of conversions with attribution inside the selected attribution scope.',
                    'Measures outcome volume that can be connected to tracked sources.',
                    'Shows how much conversion activity is explainable through the current attribution scope.',
                    [
                        'tracking coverage',
                        'source tagging quality',
                        'attribution rules',
                        'session observability',
                    ],
                    'Higher attributed-conversion counts usually suggest more conversions can be connected to source data in this scope, while lower counts usually point toward limited coverage or a smaller contribution from the scope.',
                ),
            },
            {
                aliases: ['unattributed conversions'],
                definition: definition(
                    'Number of conversions that do not have attribution coverage.',
                    'Measures outcome volume that cannot yet be tied back to tracked sources.',
                    'Helps operators see how much conversion activity is still missing source clarity.',
                    [
                        'tracking gaps',
                        'missing source data',
                        'session stitching quality',
                        'attribution logic limits',
                    ],
                    'Higher unattributed conversion counts usually suggest more outcome activity is falling outside source coverage, while lower counts usually indicate stronger attribution completeness.',
                ),
            },
            {
                aliases: ['share of attributed'],
                definition: definition(
                    'Share of all attributed conversions represented by the current attribution scope.',
                    'Measures how much of the attributed conversion pool belongs to this scope.',
                    'Helps operators compare how strongly each attribution scope contributes to the attributed result set.',
                    [
                        'scope rules',
                        'source participation',
                        'tracking coverage',
                        'conversion mix',
                    ],
                    'A higher share of attributed usually means this scope accounts for more of the conversions that have source coverage, while a lower share means it contributes a smaller slice.',
                    'Attributed Conversions in Scope / All Attributed Conversions',
                ),
            },
            {
                aliases: ['attribution coverage', 'coverage'],
                definition: definition(
                    'Share of all conversions covered by the current attribution scope.',
                    'Measures how much of the total conversion volume this scope can explain.',
                    'Shows where attribution is strong and where meaningful outcome volume still sits outside the scope.',
                    [
                        'tracking completeness',
                        'attribution method',
                        'source tagging',
                        'session stitching reliability',
                    ],
                    'Higher coverage usually suggests more of total conversions are explainable through the scope, while lower coverage usually means operators should treat source conclusions more cautiously.',
                    'Attributed Conversions in Scope / Total Conversions',
                ),
            },
            {
                aliases: ['tracked sources'],
                definition: definition(
                    'Count of distinct tracked sources present in the attribution scope.',
                    'Measures source diversity inside the scope.',
                    'Helps operators understand whether conversion contribution is concentrated in a few sources or spread across many tracked inputs.',
                    [
                        'campaign mix',
                        'channel variety',
                        'tracking taxonomy',
                        'source tagging discipline',
                    ],
                    'More tracked sources usually suggest broader source diversity in the scope, while fewer sources usually suggest attribution is concentrated in a smaller set of channels or campaigns.',
                ),
            },
            {
                aliases: ['first touch', 'first-touch'],
                definition: definition(
                    'Attribution scope that credits the earliest recorded source interaction before conversion.',
                    'Measures top-of-journey source contribution.',
                    'Helps operators understand which sources are initiating journeys that later convert.',
                    [
                        'acquisition channels',
                        'campaign discovery',
                        'top-of-funnel relevance',
                        'tracking continuity',
                    ],
                    'First-touch attribution is best used to understand sources that start journeys. It is a scope definition rather than a direct quality score for any one source.',
                ),
            },
            {
                aliases: ['last touch', 'last-touch'],
                definition: definition(
                    'Attribution scope that credits the latest recorded source interaction before conversion.',
                    'Measures closing-stage source contribution.',
                    'Helps operators understand which sources are most closely associated with the final step before outcome.',
                    [
                        'closing channels',
                        'remarketing influence',
                        'late-stage visits',
                        'tracking continuity',
                    ],
                    'Last-touch attribution is best used to understand the source closest to conversion. It should be compared with other scopes before drawing broader channel conclusions.',
                ),
            },
            {
                aliases: ['conversion touch', 'conversion-touch'],
                definition: definition(
                    'Attribution scope that credits source interactions tied to the converting touchpoint.',
                    'Measures source contribution around the actual conversion event.',
                    'Helps operators understand which sources are present at the point where conversion happens.',
                    [
                        'conversion event instrumentation',
                        'source tagging',
                        'session stitching',
                        'attribution method',
                    ],
                    'Conversion-touch attribution is best used to understand the source context closest to the recorded outcome event. It should be interpreted as scope-based context rather than as a standalone verdict.',
                ),
            },
        ],
    },
];

export function resolveMetricDefinition(
    metric: MetricDefinitionMetricLike | null | undefined,
    context?: MetricDefinitionContext | null,
): MetricDefinition | null {
    if (!metric) {
        return null;
    }

    const clusterFingerprint = normalizeToken(
        `${context?.clusterKey ?? ''} ${context?.clusterLabel ?? ''}`,
    );
    const subClusterFingerprint = normalizeToken(
        `${context?.subClusterKey ?? ''} ${context?.subClusterLabel ?? ''}`,
    );
    const metricFingerprint = normalizeToken(
        `${metric.key ?? ''} ${metric.label ?? ''}`,
    );

    const matchedScope = scopedDefinitions.find(
        (scope) =>
            matchesAliases(clusterFingerprint, scope.clusterAliases) &&
            matchesAliases(subClusterFingerprint, scope.subClusterAliases),
    );

    if (!matchedScope) {
        return null;
    }

    const matchedMetric = matchedScope.metrics.find((entry) =>
        matchesAliases(metricFingerprint, entry.aliases),
    );

    return matchedMetric?.definition ?? null;
}
