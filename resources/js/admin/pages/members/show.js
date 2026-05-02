import { initHistoryTimelines } from '../../helpers/history-timeline';

function resolveRoot(ctx) {
    if (ctx?.root?.matches?.('[data-page="members.show"]')) {
        return ctx.root;
    }

    return ctx?.root?.querySelector?.('[data-page="members.show"]')
        || document.querySelector('[data-page="members.show"]');
}

export default async function init(ctx) {
    const root = resolveRoot(ctx);
    if (!root) return;

    initHistoryTimelines(root, ctx);
}
