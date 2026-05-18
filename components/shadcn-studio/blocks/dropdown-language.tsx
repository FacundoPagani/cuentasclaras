'use client'

import { useState } from 'react'
import type { ReactNode } from 'react'

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'

type Props = {
  trigger: ReactNode
  defaultOpen?: boolean
  align?: 'start' | 'center' | 'end'
}

const LanguageDropdown = ({ defaultOpen, align, trigger }: Props) => {
  const [language, setLanguage] = useState('spanish')

  return (
    <DropdownMenu defaultOpen={defaultOpen}>
      <DropdownMenuTrigger asChild>{trigger}</DropdownMenuTrigger>
      <DropdownMenuContent className='w-50' align={align || 'end'}>
        <DropdownMenuRadioGroup value={language} onValueChange={setLanguage}>
          <DropdownMenuRadioItem value='spanish' className='data-[state=checked]:bg-accent pl-2 text-base [&>span]:hidden'>
            Espanol
          </DropdownMenuRadioItem>
          <DropdownMenuRadioItem value='english' className='data-[state=checked]:bg-accent pl-2 text-base [&>span]:hidden'>
            English
          </DropdownMenuRadioItem>
          <DropdownMenuRadioItem value='portuguese' className='data-[state=checked]:bg-accent pl-2 text-base [&>span]:hidden'>
            Portugues
          </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

export default LanguageDropdown
