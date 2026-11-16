import assert from 'node:assert/strict';
import test from 'node:test';
import { summarizeConfiguration } from '../../resources/js/sliding-door-review.js';
import { exportConfiguration } from '../../resources/js/sliding-door-export.js';
import { saveDraft, readDraft, configurationSnapshot } from '../../resources/js/sliding-door-draft.js';

function fixture() {
    return {
        config:{doorCount:2,openingWidth:3000,openingHeight:2400,modelId:1,variantId:2,frameDesignCode:'A',frameColor:'black',softClose:false,infillWeights:[1,1],doorIds:['d1','d2'],infillInstanceIdsByDoor:[['a','b'],['c','d']],infillIdsByDoor:[[7,8],[7,8]]},
        catalog:{models:[{id:1,partitionCount:1,minWidth:500,maxWidth:4000,minHeight:1600,maxHeight:2600,name:'Current system',variants:[{id:2,name:'Current variant',heightRangeMin:1600,heightRangeMax:2600,frameDesignCode:'A',color:'black',hasPartition:true,components:[]}]}],infills:[{id:7,code:'A',description:'Glass'},{id:8,code:'B',description:'Mirror'}]},
        metadata:{frameColors:[{color:'black',label:'Black'}]},
    };
}

test('review and JSON describe the exact shared infills using current catalog labels', () => {
    const {config,catalog,metadata}=fixture();
    const review=summarizeConfiguration(config,catalog,metadata);
    assert.equal(review.modelName,'Current system');
    assert.equal(review.variantName,'Current variant');
    assert.equal(review.openingWidth,3000);
    assert.equal(review.colorLabel,'Black');
    assert.equal(review.partitionCount,1);
    assert.equal(review.infillsPerDoor,2);
    assert.deepEqual(review.doors.map(door => door.infills.map(infill => infill.infill.code)),[['A','B'],['A','B']]);
    const exported=JSON.parse(exportConfiguration(config,catalog,metadata));
    assert.equal(exported.units,'mm');
    assert.deepEqual(exported.configuration,config);
    assert.deepEqual(exported.summary,review);
    assert.deepEqual(exported.assumptions,[]);
    catalog.models[0].name='Renamed system';
    assert.equal(summarizeConfiguration(config,catalog,metadata).modelName,'Renamed system');
});

test('draft round trips retain shared finishes but changed compatibility blocks export', () => {
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

test('removed products, unknown layouts and missing infills cannot be exported', () => {
    for (const mutate of [
        ({catalog}) => catalog.models=[],
        ({catalog}) => catalog.infills=[],
        ({config}) => config.variantId=999,
        ({config}) => config.infillIdsByDoor[1][0]=8,
        ({config}) => config.infillIdsByDoor[0][0]=null,
        ({config}) => config.infillWeights=[1,2],
    ]) {
        const data=fixture(); mutate(data);
        assert.throws(()=>exportConfiguration(data.config,data.catalog,data.metadata),RangeError);
    }
});

test('review resolves every recorded component from the current variant without inventing missing hardware',()=>{
    const {config,catalog,metadata}=fixture();
    const types=['side_profile','top_rail','bottom_rail','partition','soft_close_mechanism'];
    catalog.models[0].variants[0].components=types.map((type,index)=>({id:index+1,type,code:`C${index}`,description:`Part ${index}`,color:'black',incompatibleInfillIds:[]}));
    const review=summarizeConfiguration(config,catalog,metadata);
    assert.equal(review.components.length,5);
    assert.deepEqual(review.components.map(c=>[c.type,c.code,c.description,c.colorLabel]),types.map((type,index)=>[type,`C${index}`,`Part ${index}`,'Black']));
    catalog.models[0].variants[0].components.pop();
    const updated=summarizeConfiguration(config,catalog,metadata);
    assert.equal(updated.components.length,4);
    assert.ok(!updated.components.some(c=>c.type==='soft_close_mechanism'));
});
