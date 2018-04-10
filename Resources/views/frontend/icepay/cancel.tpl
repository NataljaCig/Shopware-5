{extends file='frontend/index/index.tpl'}

{* Main content *}
{block name='frontend_index_content'}
    <div class="icepay-content content custom-page--content">
        {*{if true}*}
            {include file="frontend/_includes/messages.tpl" type="error" content=$errorMessage}
        {*{/if}*}
        <br>
        <div class="icepay-content--actions">
            <a class="btn is--primary right"
               href="{url controller=account action=orders}"
               title="Order history">Order history
            </a>
        </div>
    </div>
{/block}

{block name='frontend_index_actions'}{/block}
