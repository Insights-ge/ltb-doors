import assert from 'node:assert/strict';
import test from 'node:test';
import { validateBuilder } from '../../resources/js/sliding-door-rules.js';

function fixture() {
    return {
        config:{doorCount:2,openingWidth:1600,openingHeight:2400,modelId:1,variantId:2,frameDesignCode:'A',frameColor:'black',infillWeights:[1,1],infillIdsByDoor:[[7,7],[7,7]],softClose:false},
        catalog:{models:[{id:1,partitionCount:1,minWidth:500,maxWidth:1100,minHeight:1600,maxHeight:2600,variants:[{id:2,heightRangeMin:1600,heightRangeMax:2600,frameDesignCode:'A',color:'black',hasPartition:true,hasSoftCloseMechanism:false,components:[]}]}],infills:[{id:7}]},
        metadata:{},
    };
}

test('a complete design resolves to a variant supporting its height', () => {
    const {config,catalog,metadata}=fixture();
    Object.assign(catalog.models[0].variants[0],{heightRangeMin:1600,heightRangeMax:2600});
    const result=validateBuilder(config,catalog,metadata);
    assert.deepEqual(result.errors,[]);
    assert.deepEqual(result.assumptions,[]);
    config.modelId=null;
    assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-model' && issue.step===1 && issue.code==='required'));
});

test('model size boundaries are inclusive and invalid sizes link to the first step', () => {
    const {config,catalog,metadata}=fixture();
    for (const [width,height] of [[1000,1638],[2200,2638]]) {
        Object.assign(config,{openingWidth:width,openingHeight:height});
        assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
    }
    for (const [field,value,code,limit] of [['openingWidth',999,'min',1000],['openingWidth',2201,'max',2200],['openingHeight',1637,'min',1638],['openingHeight',2639,'max',2638]]) {
        Object.assign(config,{openingWidth:1600,openingHeight:2400,[field]:value});
        assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field===`builder-${field}` && issue.step===1 && issue.code===code && issue.limit===limit));
    }
    for (const [value,code] of [[null,'required'],['abc','numeric'],[0,'gt'],[-1,'gt']]) {
        config.openingWidth=value;
        assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-openingWidth' && issue.code===code));
    }
});

test('opening limits are inclusive and constrain broader catalog ranges', () => {
    const {config, catalog, metadata} = fixture();
    const [model] = catalog.models;
    Object.assign(model, {minWidth: 400, maxWidth: 3000, minHeight: 200, maxHeight: 2800});
    Object.assign(model.variants[0], {heightRangeMin: 200, heightRangeMax: 2800});

    for (const [openingWidth, openingHeight] of [[985, 238], [5460, 2738]]) {
        Object.assign(config, {openingWidth, openingHeight});
        assert.deepEqual(validateBuilder(config, catalog, metadata).errors, []);
    }
    for (const [field, value, code, limit] of [
        ['openingWidth', '984,9', 'min', 985],
        ['openingWidth', '5460,1', 'max', 5460],
        ['openingHeight', '237,9', 'min', 238],
        ['openingHeight', '2738,1', 'max', 2738],
    ]) {
        Object.assign(config, {openingWidth: 1600, openingHeight: 2400, [field]: value});
        const {errors} = validateBuilder(config, catalog, metadata);
        assert.deepEqual(errors.find(issue => issue.field === `builder-${field}`), {code, step: 1, field: `builder-${field}`, limit});
        assert.ok(errors.some(issue => issue.field === 'builder-doorCount' && issue.code === 'in'));
    }
});

for (const partitions of [1,2,3,4]) {
    test(`model with ${partitions} partitions accepts only its fixed infill divisions`, () => {
        const {config,catalog,metadata}=fixture();
        catalog.models[0].partitionCount=partitions;
        config.infillWeights=Array(partitions+1).fill(1);
        config.infillIdsByDoor=[Array(partitions+1).fill(7),Array(partitions+1).fill(7)];
        assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
        config.infillWeights=[1];
        assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.code==='model_structure_changed'));
    });
}

test('compatibility checks every used component and enables soft-close exclusions only when selected', () => {
    const {config,catalog,metadata}=fixture();
    const variant=catalog.models[0].variants[0];
    for (const type of ['side_profile','top_rail','bottom_rail','partition']) {
        variant.components=[{type,incompatibleInfillIds:[7]}];
        assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue=>issue.code==='finish_incompatible').length,2);
    }
    variant.components=[{type:'soft_close_mechanism',incompatibleInfillIds:[7]}];
    assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
    config.softClose=true;
    variant.hasSoftCloseMechanism=true;
    assert.equal(validateBuilder(config,catalog,metadata).errors.filter(issue=>issue.code==='finish_incompatible').length,2);
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
    config.infillIdsByDoor[0][1]=999;
    let result=validateBuilder(config,catalog,metadata);
    assert.ok(result.errors.some(issue=>issue.field==='builder-infill-0' && issue.doorIndex===0 && issue.code==='required'));
    assert.ok(result.errors.some(issue=>issue.field==='builder-infill-1' && issue.doorIndex===0 && issue.code==='exists'));
    catalog.models[0].variants=[];
    assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-variant' && issue.code==='exists'));
});

test('unsupported soft close remains a blocker while discouraged variant sizing stays advisory', () => {
    const {config,catalog,metadata}=fixture();
    config.softClose=true;
    Object.assign(catalog.models[0],{notRecommendedHeightMin:2300,notRecommendedHeightMax:2500});
    const result=validateBuilder(config,catalog,metadata);
    assert.ok(result.errors.some(issue=>issue.code==='variant_components_mismatch'));
    assert.deepEqual(result.assumptions,[]);
    assert.ok(result.advisories.some(issue=>issue.code==='discouraged_height_reference'));
});

test('2688 remains selectable with a warning above the recommended catalog maximum', () => {
    const {config,catalog,metadata}=fixture();
    Object.assign(catalog.models[0],{notRecommendedHeightMin:2601,notRecommendedHeightMax:2700});
    for (const height of [2639,2688,2738]) {
        config.openingHeight=height;
        const result=validateBuilder(config,catalog,metadata);
        assert.deepEqual(result.errors,[]);
        assert.ok(result.advisories.some(issue=>issue.code==='discouraged_height_reference'));
    }
    config.openingHeight=2739;
    assert.ok(validateBuilder(config,catalog,metadata).errors.length > 0);
});

test('model height warning appears on the first step before a variant is selected', () => {
    const {config,catalog,metadata}=fixture();
    Object.assign(catalog.models[0],{notRecommendedHeightMin:2601,notRecommendedHeightMax:2700});
    config.openingHeight=2650;
    config.variantId=null;
    config.frameDesignCode=null;
    assert.deepEqual(validateBuilder(config,catalog,metadata).advisories, [
        {code:'discouraged_height_reference',step:1,field:'builder-openingHeight'},
    ]);
    config.modelId=null;
    assert.deepEqual(validateBuilder(config,catalog,metadata).advisories,[]);
});

test('height warning appears only within the inclusive discouraged range', () => {
    const {config,catalog,metadata}=fixture();
    Object.assign(catalog.models[0],{notRecommendedHeightMin:2300,notRecommendedHeightMax:2500});
    for (const height of [2338,2400,2538]) {
        config.openingHeight=height;
        assert.ok(validateBuilder(config,catalog,metadata).advisories.some(issue=>issue.code==='discouraged_height_reference'));
    }
    for (const height of [2337,2539,null,'']) {
        config.openingHeight=height;
        assert.deepEqual(validateBuilder(config,catalog,metadata).advisories,[]);
    }
});

test('height and components cannot be combined into a variant absent from the catalog', () => {
    const {config,catalog,metadata}=fixture();
    const variant=catalog.models[0].variants[0];
    variant.heightRangeMax=2000;
    let errors=validateBuilder(config,catalog,metadata).errors;
    assert.ok(errors.some(issue=>issue.code==='no_variant_for_height'));
    assert.ok(errors.some(issue=>issue.code==='variant_height_mismatch'));
    config.openingHeight=2000;
    variant.hasSoftCloseMechanism=true;
    variant.components=[{type:'soft_close_mechanism',incompatibleInfillIds:[7]}];
    errors=validateBuilder(config,catalog,metadata).errors;
    assert.ok(errors.some(issue=>issue.code==='variant_components_mismatch'));
    assert.equal(errors.filter(issue=>issue.code==='finish_incompatible').length,2);
    config.softClose=true;
    variant.components[0].incompatibleInfillIds=[];
    assert.deepEqual(validateBuilder(config,catalog,metadata).errors,[]);
    config.frameDesignCode='invented';
    assert.ok(validateBuilder(config,catalog,metadata).errors.some(issue=>issue.field==='builder-variant'));
});
