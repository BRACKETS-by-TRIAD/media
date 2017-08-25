<?php

namespace Brackets\Media\Test;

class TestModelWithCollectionsDisabledAutoProcess extends TestModelWithCollections
{
    public function shouldAutoProcessMedia()
    {
        return false;
    }
}