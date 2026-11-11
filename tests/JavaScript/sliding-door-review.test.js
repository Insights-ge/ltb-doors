import assert from 'node:assert/strict';
import test from 'node:test';
import { summarizeConfiguration } from '../../resources/js/sliding-door-review.js';
import { exportConfiguration } from '../../resources/js/sliding-door-export.js';
import { saveDraft, readDraft, configurationSnapshot } from '../../resources/js/sliding-door-draft.js';

function fixture() {
    return {
        config:{openingWidth:3000,openingHeight:2400,modelId:1,variantId:2,frameDesignCode:'A',frameColor:'black',softClose:false,panelWeights:[1,1],doorIds:['d1','d2'],panelIdsByDoor:[['a','b'],['c','d']],infillIdsByDoor:[[7,8],[8,7]]},
        catalog:{models:[{id:1,partitionCount:1,minWidth:500,maxWidth:4000,minHeight:1600,maxHeight:2600,name:'Current system',variants:[{id:2,name:'Current variant',heightRangeMin:1600,heightRangeMax:2600,frameDesignCode:'A',color:'black',hasPartition:true,components:[]}]}],infills:[{id:7,code:'A',description:'Glass'},{id:8,code:'B',description:'Mirror'}]},
        metadata:{frameColors:[{color:'black',label:'Black'}]},
    };
}

test('review and JSON describe the exact independently edited panels using current catalog labels', () => {
    const {config,catalog,metadata}=fixture();
    const review=summarizeConfiguration(config,catalog,metadata);
    assert.equal(review.modelName,'Current system');
    assert.equal(review.variantName,'Current variant');
    assert.equal(review.openingWidth,3000);
    assert.equal(review.colorLabel,'Black');
    assert.equal(review.partitionCount,1);
    assert.equal(review.panelsPerDoor,2);
    assert.deepEqual(review.doors.map(door => door.panels.map(panel => panel.infill.code)),[['A','B'],['B','A']]);
    const exported=JSON.parse(exportConfiguration(config,catalog,metadata));
    assert.equal(exported.units,'mm');
    assert.deepEqual(exported.configuration,config);
    assert.deepEqual(exported.summary,review);
    assert.deepEqual(exported.assumptions,[]);
    catalog.models[0].name='Renamed system';
    assert.equal(summarizeConfiguration(config,catalog,metadata).modelName,'Renamed system');
});

test('draft round trips retain independent finishes but changed compatibility blocks export', () => {
    const {config,catalog,metadata}=fixture();
    let raw;
    const storage={setItem:(key,value)=>raw=value,getItem:()=>raw};
    saveDraft(storage,'user',config);
    let restored=readDraft(storage,'user',catalog,metadata);
    assert.deepEqual(configurationSnapshot(restored.configuration),config);
    catalog.models[0].variants[0].components=[{type:'top_rail',incompatibleInfillIds:[8]}];
    restored=readDraft(storage,'user',catalog,metadata);
    assert.equal(restored.status,'outdated');
    assert.throws(()=>exportConfiguration(restored.configuration,catalog,metadata),RangeError);
    assert.deepEqual(restored.configuration.infillIdsByDoor,config.infillIdsByDoor);
});

test('removed products, unknown layouts and missing panels cannot be exported', () => {
    for (const mutate of [
        ({catalog}) => catalog.models=[],
        ({catalog}) => catalog.infills=[],
        ({config}) => config.variantId=999,
        ({config}) => config.infillIdsByDoor[0][0]=null,
        ({config}) => config.panelWeights=[1,2],
    ]) {
        const data=fixture(); mutate(data);
        assert.throws(()=>exportConfiguration(data.config,data.catalog,data.metadata),RangeError);
    }
});
