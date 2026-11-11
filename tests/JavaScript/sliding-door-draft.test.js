import assert from 'node:assert/strict';
import test from 'node:test';
import { configurationSnapshot, saveDraft, readDraft } from '../../resources/js/sliding-door-draft.js';

test('draft saving whitelists configuration facts and reports unavailable storage', () => {
    const config={openingWidth:3000,panelWeights:[1], secret:'not saved'};
    const snapshot=configurationSnapshot(config);
    assert.equal(snapshot.secret,undefined);
    snapshot.panelWeights.push(1);
    assert.deepEqual(config.panelWeights,[1]);
    let saved;
    assert.equal(saveDraft({setItem:(key,value)=>saved=[key,JSON.parse(value)]},'user-1',config),'saved');
    assert.equal(saved[0],'user-1');
    assert.equal(saved[1].openingWidth,3000);
    assert.equal(saveDraft({setItem:()=>{throw Error('quota');}},'user-1',config),'unavailable');
});


function fixture() {
    return {
        config:{ openingWidth:3000,openingHeight:2400,modelId:1,variantId:2,frameColor:'black',frameDesignCode:'A',softClose:false,panelWeights:[1],doorIds:['d1','d2'],panelIdsByDoor:[['p1'],['p2']],infillIdsByDoor:[[7],[7]] },
        catalog:{models:[{id:1,name:'System',variants:[{id:2,name:'Variant',color:'black',frameDesignCode:'A',components:[]}]}],infills:[{id:7,code:'A',description:'Finish'}]},
        metadata:{layoutPresets:[{id:'full_panel',label:'Full',panelWeights:[1]}],frameColors:[{color:'black',label:'Black'}]},
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
    assert.deepEqual(result.configuration.infillIdsByDoor,[[7],[7]]);
});
