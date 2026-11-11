import assert from 'node:assert/strict';
import test from 'node:test';
import { configurationSnapshot, saveDraft } from '../../resources/js/sliding-door-draft.js';

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
