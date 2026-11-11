import assert from 'node:assert/strict';
import test from 'node:test';
import slidingDoorBuilder from '../../resources/js/sliding-door-builder.js';
import { reconcilePreviewConfiguration, previewSelectionTargets } from '../../resources/js/sliding-door-selection.js';

function builder() {
    const model={id:1,partitionCount:2,minWidth:500,maxWidth:1100,minHeight:1600,maxHeight:2600,variants:[{id:11,frameDesignCode:'A',color:'black',heightRangeMin:1600,heightRangeMax:2600,hasPartition:true,hasSoftCloseMechanism:false,components:[]}]};
    const b=slidingDoorBuilder({initialStep:1,payload:{catalog:{models:[model],infills:[{id:7},{id:8}]},metadata:{frameColors:[],infillVisuals:[],neutralVisual:{fill:'#B8B8B8'}}},fieldValidationMessages:{'builder-panel-0':{required:'Panel 1 is required.'}}});
    let id=0;
    b.previewConfiguration=reconcilePreviewConfiguration({modelId:1,variantId:11,frameDesignCode:'A',frameColor:'black',openingWidth:1600,openingHeight:2400,panelWeights:[1,1,1],infillIdsByDoor:[[null,null,null],[null,null,null]],softClose:false},null,()=>`id-${++id}`);
    b.previewDoors=previewSelectionTargets(b.previewConfiguration);
    b.sizeInputs={openingWidth:'1600',openingHeight:'2400'};
    b.$dispatch=()=>{};
    return b;
}

test('every panel field can edit both doors without selecting a preview panel',()=>{
    const b=builder();
    b.applyInfillField(['7','8','7']);
    assert.deepEqual(b.previewConfiguration.infillIdsByDoor,[[7,8,7],[7,8,7]]);
    assert.notEqual(b.previewConfiguration.infillIdsByDoor[0],b.previewConfiguration.infillIdsByDoor[1]);
    assert.deepEqual(b.previewSelection,{doorId:null,panelId:null});
    b.applyInfillField([7,null,8]);
    assert.deepEqual(b.previewConfiguration.infillIdsByDoor,[[7,null,8],[7,null,8]]);
});

test('clicking either preview door selects only a shared panel position',()=>{
    const b=builder();
    b.selectPreviewPanel(b.previewDoors[1].id,b.previewDoors[1].panels[2].id);
    assert.equal(b.selectedPreviewPanel.index,2);
    assert.equal(b.previewSelection.doorId,b.previewDoors[0].id);
    b.chooseInfill(8);
    assert.deepEqual(b.previewConfiguration.infillIdsByDoor,[[null,null,8],[null,null,8]]);
});

test('incompatible or unknown choices cannot overwrite a shared panel',()=>{
    const b=builder();
    b.catalog.models[0].variants[0].components=[{type:'top_rail',incompatibleInfillIds:[8]}];
    b.applyInfillField([7,8,999]);
    assert.deepEqual(b.previewConfiguration.infillIdsByDoor,[[7,null,null],[7,null,null]]);
    assert.deepEqual(b.infillState,[7,null,null]);
});

test('all panel errors appear inline after Next without requiring selection',async()=>{
    const b=builder();
    b.activeStep=3;
    await b.goToStep(4);
    assert.equal(b.activeStep,3);
    assert.equal(b.fieldError('builder-panel-0'),'Panel 1 is required.');
    b.applyInfillField([7,8,7]);
    await b.goToStep(4);
    assert.equal(b.activeStep,4);
    assert.equal(b.canReview,true);
});

test('model reduction confirms removal and expansion does not resurrect shared finishes',()=>{
    const b=builder();
    b.applyInfillField([7,8,7]);
    b.catalog.models.push({...b.catalog.models[0],id:2,partitionCount:1});
    b.formState={modelId:2,variantId:null,frameDesignCode:null,openingWidth:'1600',openingHeight:'2400'};
    const before=b.previewConfiguration;
    b.applyFormFields();
    assert.equal(b.pendingChange.kind,'model');
    assert.equal(b.previewConfiguration,before);
    b.confirmConfigurationChange();
    assert.deepEqual(b.previewConfiguration.infillIdsByDoor,[[7,8],[7,8]]);
    b.formState.modelId=1;
    b.applyFormFields();
    assert.deepEqual(b.previewConfiguration.infillIdsByDoor,[[7,8,null],[7,8,null]]);
});

test('reset clears all shared fields and configuration',()=>{
    const b=builder();
    b.applyInfillField([7,8,7]);
    b.resetConfiguration();
    b.syncInfillField();
    assert.deepEqual(b.infillState,[]);
    assert.equal(b.previewConfiguration.modelId,null);
    assert.equal(b.activeStep,1);
});
