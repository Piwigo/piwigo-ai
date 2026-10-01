<div class="flex! items-center gap-2.5 text-xs mt-2">
  <label class="font-checkbox flex! items-center tiptip" title="{'Automatically generate a description for each uploaded photo using AI'|translate}">
    <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
    <input type="checkbox" name="p_ai_caption" id="pAiBatchCaption" value="1" checked>
    {'Description'|translate}
  </label>

  <label class="font-checkbox flex! items-center tiptip" title="{'Automatically assign tags to each uploaded photo using AI'|translate}">
    <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
    <input type="checkbox" name="p_ai_tagging" id="pAiBatchTagging" value="1" checked>
    {'Tags'|translate}
  </label>

  <label class="font-checkbox flex! items-center tiptip" title="{'Extract and index text found in each uploaded photo using AI'|translate}">
    <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
    <input type="checkbox" name="p_ai_ocr" id="pAiBatchOCR" value="1" checked>
    {'OCR'|translate}
  </label>

  {if $P_AI_EMBEDDING_ENABLED}
  <label class="font-checkbox flex! items-center tiptip{if $P_AI_EMBEDDING_REQUIRED} opacity-50{/if}" title="{if $P_AI_EMBEDDING_REQUIRED}{'Required by the smart and closed tags modes, which choose tags with the photo embedding'|translate}{else}{'Compute the embedding of each photo and of its tags, used to choose the tags among the indexed ones, to find similar photos and to search in natural language'|translate}{/if}">
    <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
    <input type="checkbox" name="p_ai_embedding" id="pAiBatchEmbedding" value="1" checked{if $P_AI_EMBEDDING_REQUIRED} disabled{/if}>
    {'Embedding'|translate}
  </label>
  {/if}
</div>
{if $P_AI_TAGS_NOT_INDEXED}
<p class="text-xs italic mt-1"><i class="icon-attention"></i> {'%d tags of the gallery are not indexed: they cannot be chosen until they are indexed, from the Piwigo AI overview.'|translate:$P_AI_TAGS_NOT_INDEXED}</p>
{/if}
