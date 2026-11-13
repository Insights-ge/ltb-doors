import assert from 'node:assert/strict';
import test from 'node:test';
import { configurationSnapshot, saveDraft, readDraft } from '../../resources/js/sliding-door-draft.js';

test('draft saving whitelists configuration facts and reports unavailable storage', () => {
    const config={openingWidth:3000,infillWeights:[1], secret:'not saved'};
    const snapshot=configurationSnapshot(config);
    assert.equal(snapshot.secret,undefined);
    snapshot.infillWeights.push(1);
    assert.deepEqual(config.infillWeights,[1]);
    let saved;
    assert.equal(saveDraft({setItem:(key,value)=>saved=[key,JSON.parse(value)]},'user-1',config),'saved');
    assert.equal(saved[0],'user-1');
    assert.equal(saved[1].openingWidth,3000);
    assert.equal(saveDraft({setItem:()=>{throw Error('quota');}},'user-1',config),'unavailable');
});


function fixture() {
    return {
        config:{ doorCount:2,openingWidth:3000,openingHeight:2400,modelId:1,variantId:2,frameColor:'black',frameDesignCode:'A',softClose:false,infillWeights:[1,1],doorIds:['d1','d2'],infillInstanceIdsByDoor:[['p1','p3'],['p2','p4']],infillIdsByDoor:[[7,7],[7,7]] },
        catalog:{models:[{id:1,partitionCount:1,minWidth:500,maxWidth:4000,minHeight:1600,maxHeight:2600,name:'System',variants:[{id:2,name:'Variant',color:'black',heightRangeMin:1600,heightRangeMax:2600,frameDesignCode:'A',hasPartition:true,components:[]}]}],infills:[{id:7,code:'A',description:'Finish'}]},
        metadata:{frameColors:[{color:'black',label:'Black'}]},
    };
}

test('draft round trips preserve identities and recheck the current catalog', () => {
    const {config,catalog,metadata}=fixture();
    let raw;
    const storage={setItem:(key,value)=>raw=value,getItem:()=>raw};
    saveDraft(storage,'draft',config);
    let result=readDraft(storage,'draft',catalog,metadata);
    assert.equal(result.status,'restored');
    assert.deepEqual(configurationSnapshot(result.configuration),config);
    catalog.infills=[];
    result=readDraft(storage,'draft',catalog,metadata);
    assert.equal(result.status,'outdated');
    assert.deepEqual(result.configuration.infillIdsByDoor,[[7,7],[7,7]]);
});


test('malformed, oversized and legacy draft shapes are rejected without breaking configuration', () => {
    const {config,catalog,metadata}=fixture();
    for (const raw of ['{','null','[]',JSON.stringify({openingWidth:3000}),' '.repeat(100001),JSON.stringify({...config,doorIds:['duplicate','duplicate']}),JSON.stringify({...config,infillIdsByDoor:[[7]]}),JSON.stringify({...config,frameDesignCode:'A'.repeat(256)})]) {
        const result=readDraft({getItem:()=>raw},'draft',catalog,metadata);
        assert.equal(result.status,'invalid');
        assert.equal(result.configuration,null);
    }
    assert.equal(readDraft({getItem:()=>null},'draft',catalog,metadata).status,null);
    assert.equal(readDraft({getItem:()=>{throw Error('denied');}},'draft',catalog,metadata).status,'unavailable');
});

test('changed model divisions preserve the stored draft for explicit recovery', () => {
    const {config,catalog,metadata}=fixture();
    const raw=JSON.stringify(config);
    catalog.models[0].partitionCount=4;
    const result=readDraft({getItem:()=>raw},'draft',catalog,metadata);
    assert.equal(result.status,'outdated_layout');
    assert.equal(result.configuration,null);
    assert.deepEqual(JSON.parse(raw),config);
});

test('older independently filled doors require explicit recovery without losing the stored design',()=>{
    const {config,catalog,metadata}=fixture();
    config.infillIdsByDoor[1][0]=null;
    const raw=JSON.stringify(config);
    const result=readDraft({getItem:()=>raw},'draft',catalog,metadata);
    assert.equal(result.status,'outdated_infills');
    assert.equal(result.configuration,null);
    assert.deepEqual(JSON.parse(raw).infillIdsByDoor,config.infillIdsByDoor);
});

test('previous saved field names restore into the infill contract without changing instance identities',()=>{
    const {config,catalog,metadata}=fixture();
    const legacy={...config,panelWeights:config.infillWeights,panelIdsByDoor:config.infillInstanceIdsByDoor};
    delete legacy.infillWeights;
    delete legacy.infillInstanceIdsByDoor;
    const raw=JSON.stringify(legacy);
    const result=readDraft({getItem:()=>raw},'draft',catalog,metadata);
    assert.equal(result.status,'restored');
    assert.deepEqual(configurationSnapshot(result.configuration),config);
    assert.ok(!Object.hasOwn(configurationSnapshot(result.configuration),'panelWeights'));
});

test('two-to-five-door drafts round trip and old drafts without a count keep two doors', () => {
    const {config,catalog,metadata}=fixture();
    const old={...config};
    delete old.doorCount;
    assert.equal(readDraft({getItem:()=>JSON.stringify(old)},'draft',catalog,metadata).configuration.doorCount,2);
    for (const count of [2,3,4,5]) {
        const next={...config,doorCount:count,doorIds:Array.from({length:count},(_,i)=>`door-${i}`),
            infillInstanceIdsByDoor:Array.from({length:count},(_,i)=>[`a-${i}`,`b-${i}`]),
            infillIdsByDoor:Array.from({length:count},()=>[7,7])};
        const restored=readDraft({getItem:()=>JSON.stringify(next)},'draft',catalog,metadata);
        assert.equal(restored.status,'restored');
        assert.deepEqual(configurationSnapshot(restored.configuration),next);
    }
    for (const doorCount of [0,1,6,2.5,'3']) {
        assert.equal(readDraft({getItem:()=>JSON.stringify({...config,doorCount})},'draft',catalog,metadata).status,'invalid');
    }
});
