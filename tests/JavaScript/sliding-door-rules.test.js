import assert from 'node:assert/strict';
import test from 'node:test';
import { validateBuilder } from '../../resources/js/sliding-door-rules.js';

function fixture() {
    return {
        config:{openingWidth:800,openingHeight:2400,modelId:1,variantId:2,frameDesignCode:'A',frameColor:'black',panelWeights:[1,1],infillIdsByDoor:[[7,7],[7,7]],softClose:false},
        catalog:{models:[{id:1,partitionCount:1,minWidth:500,maxWidth:1100,minHeight:1600,maxHeight:2600,variants:[{id:2,frameDesignCode:'A',color:'black',hasPartition:true,hasSoftCloseMechanism:false,components:[]}]}],infills:[{id:7}]},
        metadata:{},
    };
}

test('a complete design obeys model sizing and keeps variant sizing provisional', () => {
    const {config,catalog,metadata}=fixture();
    Object.assign(catalog.models[0].variants[0],{heightRangeMin:1600,heightRangeMax:2000});
    const result=validateBuilder(config,catalog,metadata);
    assert.deepEqual(result.errors,[]);
    assert.equal(result.assumptions[0].code,'sizing_unconfirmed');
    config.modelId=null;
    assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-model' && issue.step===1 && issue.code==='required'));
});

test('model size boundaries are inclusive and invalid sizes link to the first step', () => {
    const {config,catalog,metadata}=fixture();
    for (const [width,height] of [[500,1600],[1100,2600]]) {
        Object.assign(config,{openingWidth:width,openingHeight:height});
        assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
    }
    for (const [field,value,code,limit] of [['openingWidth',499,'min',500],['openingWidth',1101,'max',1100],['openingHeight',1599,'min',1600],['openingHeight',2601,'max',2600]]) {
        Object.assign(config,{openingWidth:800,openingHeight:2400,[field]:value});
        assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field===`builder-${field}` && issue.step===1 && issue.code===code && issue.limit===limit));
    }
    for (const [value,code] of [[null,'required'],['abc','numeric'],[0,'gt'],[-1,'gt']]) {
        config.openingWidth=value;
        assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-openingWidth' && issue.code===code));
    }
});

for (const partitions of [1,2,3,4]) {
    test(`model with ${partitions} partitions accepts only its fixed panel divisions`, () => {
        const {config,catalog,metadata}=fixture();
        catalog.models[0].partitionCount=partitions;
        config.panelWeights=Array(partitions+1).fill(1);
        config.infillIdsByDoor=[Array(partitions+1).fill(7),Array(partitions+1).fill(7)];
        assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
        config.panelWeights=[1];
        assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.code==='model_structure_changed'));
    });
}

test('compatibility checks every used component and enables soft-close exclusions only when selected', () => {
    const {config,catalog,metadata}=fixture();
    const variant=catalog.models[0].variants[0];
    for (const type of ['side_profile','top_rail','bottom_rail','partition']) {
        variant.components=[{type,incompatibleInfillIds:[7]}];
        assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue=>issue.code==='finish_incompatible').length,4);
    }
    variant.components=[{type:'soft_close_mechanism',incompatibleInfillIds:[7]}];
    assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
    config.softClose=true;
    variant.hasSoftCloseMechanism=true;
    assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue=>issue.code==='finish_incompatible').length,4);
});

test('variants without required partition hardware cannot be reviewed', () => {
    const {config,catalog,metadata}=fixture();
    catalog.models[0].variants[0].hasPartition=false;
    const before=structuredClone(config);
    const result=validateBuilder(config,catalog,metadata);
    assert.ok(result.errors.some(issue=>issue.code==='model_unavailable' && issue.step===1));
    assert.ok(result.errors.some(issue=>issue.code==='partition_missing' && issue.field==='builder-variant'));
    assert.deepEqual(config,before);
});

test('removed products and finishes stay present but invalidate the design', () => {
    const {config,catalog,metadata}=fixture();
    config.infillIdsByDoor[0][0]=null;
    config.infillIdsByDoor[1][0]=999;
    let result=validateBuilder(config,catalog,metadata);
    assert.ok(result.errors.some(issue=>issue.field==='builder-finishes' && issue.doorIndex===0 && issue.code==='required'));
    assert.ok(result.errors.some(issue=>issue.field==='builder-finishes' && issue.doorIndex===1 && issue.code==='exists'));
    catalog.models[0].variants=[];
    assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-variant' && issue.code==='exists'));
});

test('unsupported soft close remains a blocker while discouraged variant sizing stays advisory', () => {
    const {config,catalog,metadata}=fixture();
    config.softClose=true;
    Object.assign(catalog.models[0],{notRecommendedHeightMin:2300,notRecommendedHeightMax:2500});
    const result=validateBuilder(config,catalog,metadata);
    assert.ok(result.errors.some(issue=>issue.code==='soft_close_missing'));
    assert.ok(result.assumptions.some(issue=>issue.code==='soft_close_provisional'));
    assert.ok(result.advisories.some(issue=>issue.code==='discouraged_height_reference'));
});
