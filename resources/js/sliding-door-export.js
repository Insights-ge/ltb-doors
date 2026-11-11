import { configurationSnapshot } from './sliding-door-draft.js';
import { summarizeConfiguration } from './sliding-door-review.js';
import { validateBuilder } from './sliding-door-rules.js';

export function exportConfiguration(configuration, catalog, metadata) {
    const validation=validateBuilder(configuration,catalog,metadata);
    if (validation.errors.length) throw new RangeError('Cannot export an invalid configuration.');
    return JSON.stringify({
        units: 'mm',
        configuration: configurationSnapshot(configuration),
        summary: summarizeConfiguration(configuration,catalog,metadata),
        assumptions: validation.assumptions.map(issue => issue.code),
    }, null, 2);
}
